<?php
/*
	Copyright (c) 2015, Zhaofeng Li
	All rights reserved.
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	* Redistributions of source code must retain the above copyright notice, this
	list of conditions and the following disclaimer.
	* Redistributions in binary form must reproduce the above copyright notice,
	this list of conditions and the following disclaimer in the documentation
	and/or other materials provided with the distribution.
	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
	AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
	FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
	DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
	SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
	CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
	OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
	OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Feng\LinkyBot;

use Irazasyed\Telegram\Telegram;
use Irazasyed\Telegram\Objects\Update;

class Bot {
	protected $api = null;
	protected $apiOffset = 0;
	protected $config = array();
	protected $linkPattern = "";
	protected $templatePattern = "";
	protected $interwikiPattern = "";
	protected $cacheInterwikiList = array();
	protected $cacheInterwikiMap = array();
	protected $cacheInterwikiApiMap = array();
	protected $defaultWiki = "en";
	protected $defaultLanguage = "en";
	protected $chatPreferences = array();

	public function __construct( $config ) {
		$this->config = $config;
		$this->api = new Telegram( $this->getConfig( "key" ) );
		$this->linkPattern = "/"
		                   . "\[\["
		                   . "(?'pagename'[^\#\<\>\[\]\|\{\}]+)"
		                   . "(\|(?'caption'[^\[\]]+))?"
		                   . "\]\]"
		                   . "/";
		$this->templatePattern = "/"
		                       . "\{\{"
		                       . "(?'templatename'[^\#\<\>\[\]\|\{\}]+)"
		                       . "(\|(?'param'[^\}]+))?"
		                       . "\}\}"
		                       . "/";
		$this->interwikiPattern = "/^"
		                        . "\:?"
		                        . "(?'wiki'[A-Za-z]+)\:"
		                        . "(?'pagename'.*)"
		                        . "$/";
		$this->rebuildCache();
		if ( $this->getConfig( "preserveChatPreferences" ) ) {
			$this->chatPreferences = json_decode( file_get_contents( $this->getConfig( "chatPreferencesFile" ) ), true );
		}
		ini_set( "user_agent", "LinkyBot/0.1 (Expands wikilinks on Telegram (Try adding @linkybot to your group!); [[en:User:Zhaofeng Li]])" );
	}

	public function rebuildCache() {
		$this->cacheInterwikiList = array();
		$this->cacheInterwikiMap = array();
		foreach ( $this->getConfig( "interwikiMap" ) as $farm ) {
			foreach ( $farm['wikis'] as $name => $wiki ) {
				if ( is_array( $wiki ) ) {
					$realwiki = $wiki[1];
					$wiki = $wiki[0];
				} else {
					$realwiki = $wiki;
				}
				$this->cacheInterwikiList[] = $wiki;
				$this->cacheInterwikiMap[$wiki] = str_replace( "%wiki%", $realwiki, $farm['pattern'] );
				if ( !empty( $farm['api'] ) ) {
					$this->cacheInterwikiApiMap[$wiki] = str_replace( "%wiki%", $realwiki, $farm['api'] );
				}
			}
		}
	}

	public function sanityCheck() {
		try {
			$this->api->getMe();
		} catch ( \Exception $e ) {
			return false;
		}
		return true;
	}

	public function processUpdate( Update $update ) {
		foreach ( $update as $u ) {
			if ( $u['update_id'] < $this->apiOffset ) {
				continue;
			}
			$this->apiOffset = $u['update_id'] + 1;
			if ( !empty( $u['message']['text'] ) ) {
				$chatId = $u['message']['chat']['id'];
				$fromId = $u['message']['from']['id'];
				$messageId = $u['message']['message_id'];
				$isPm = isset( $u['message']['chat']['username'] );
				$text = $u['message']['text'];
				$this->loadChatPreferences( $chatId );
				if ( "/setDefaultWiki" == substr( $text, 0, 15 ) ) {
					$wiki = substr( $text, 16 );
					if ( $this->wikiSupported( $wiki ) ) {
						$this->setChatPreferences( $chatId, "defaultWiki", substr( $text, 16 ) );
						$this->api->sendMessage( $chatId, "👌", false, $messageId );
					} else {
						$this->api->sendMessage( $chatId, "😕", false, $messageId );
					}
				} else if ( "/setDefaultLanguage" == substr( $text, 0, 19 ) ) {
					$this->setChatPreferences( $chatId, "defaultLanguage", substr( $text, 20 ) );
					$this->api->sendMessage( $chatId, "👌", false, $messageId );
				} else {
					$matches = array();
					$response = "";
					if ( preg_match_all( $this->linkPattern, $text, $matches ) ) {
						foreach ( $matches['pagename'] as $index => $pagename ) {
							$caption = !empty( $matches['caption'][$index] ) ? $matches['caption'][$index] : "";
							if ( false !== $link = $this->getPageResponse( $pagename, $caption ) ) {
								$response .= "$link\r\n";
							}
						}
					}
					if ( preg_match_all( $this->templatePattern, $text, $matches ) ) {
						foreach ( $matches['templatename'] as $index => $templatename ) {
							if ( false !== $link = $this->getTemplateResponse( $templatename ) ) {
								$response .= "$link\r\n";
							}
						}
					}
					$response = rtrim( $response, "\r\n" );
					if ( !empty( $response ) ) {
						$this->api->sendMessage( $chatId, $response, false, $messageId, "Markdown" );
					}
				}
			}
		}
	}

	public function getPageResponse( $pagename, $caption = "" ) {
		$wiki = "";
		$realpagename = "";
		if ( false !== $this->resolveInterwiki( $pagename, $wiki, $realpagename ) ) {
			return $this->getResponse( $wiki, $realpagename, $caption );
		}
		return false;
	}

	public function getTemplateResponse( $pagename ) {
		$ns = "";
		$realpagename = "";
		if ( false !== $this->resolveNamespace( $pagename, "Template", $ns, $realpagename ) ) {
			if (
				strtolower( $ns ) == "template" &&
				in_array( $realpagename, $this->getConfig( "magicwords" ) )
			) {
				return $this->getResponse( "mw", "Help:Magic words" );
			}
			return $this->getResponse( $this->defaultWiki, "$ns:$realpagename" );
		}
		return false;
	}

	public function loadChatPreferences( $chatId ) {
		$this->defaultWiki = $this->getChatPreferences( $chatId, "defaultWiki" );
		$this->defaultLanguage = $this->getChatPreferences( $chatId, "defaultLanguage" );
	}

	public function getChatPreferences( $chatId, $key ) {
		if ( isset( $this->chatPreferences[$chatId][$key] ) ) return $this->chatPreferences[$chatId][$key];
		else return $this->getConfig( $key );
	}

	public function setChatPreferences( $chatId, $key, $value ) {
		$this->chatPreferences[$chatId][$key] = $value;
		if ( $this->getConfig( "preserveChatPreferences" ) ) {
			$json = json_encode( $this->chatPreferences );
			file_put_contents( $this->getConfig( "chatPreferencesFile" ), $json );
		}
	}

	public function run() {
		for ( ; ; ) {
			$update = $this->api->getUpdates( $this->apiOffset, 100, 30 );
			$this->processUpdate( $update );
		}
	}

	protected function getResponse( $wiki, $pagename, $caption = "" ) {
		$captions = $this->getConfig( "captions" );
		if ( !$this->wikiSupported( $wiki ) ) return false;
		if ( empty( $caption ) ) {
			$lcpagename = strtolower( $pagename );
			$safepagename = urlencode( $this->encodePageName( $pagename ) );
			if (
				$this->apiSupported( $wiki ) &&
				"user:" == strtolower( substr( $pagename, 0, 5 ) )
			) {
				// FIXME: This should probably be separated
				$api = $this->cacheInterwikiApiMap[$wiki] . "?format=json&action=query&list=users&ususers=" . $safepagename . "&usprop=groups|gender|rights";
				$response = json_decode( file_get_contents( $api ), true );
				if ( !isset( $response['query']['users'][0]['missing'] ) ) {
					$userinfo = $response['query']['users'][0];
					// gender
					if ( "male" == $userinfo['gender'] ) $caption .= $captions['gender-male'];
					else if ( "female" == $userinfo['gender'] ) $caption .= $captions['gender-female'];
					else $caption .= $captions['gender-unknown'];
					// user groups
					if ( in_array( "sysop", $userinfo['groups'] ) ) $caption .= $captions['groups-sysop'];
					else {
						if ( in_array( "rollback", $userinfo['rights'] ) ) $caption .= $captions['groups-rollback'];
						// TODO: more groups?
					}
				} else {
					$caption = $captions['apifailed'];
				}
			} else if ( !empty( $this->getConfig( "captionOverrides" )[$lcpagename] ) ) {
				$caption = $this->getConfig( "captionOverrides" )[$lcpagename];

			} else {
				$caption = $this->cleanPageName( $pagename );
			}
		}

		$url = str_replace( "%pagename%", $this->encodePageName( $pagename ), $this->cacheInterwikiMap[$wiki] );
		$url = str_replace( "%defaultLanguage%", $this->defaultLanguage, $url );
		$url = str_replace( "%defaultWiki%", $this->defaultWiki, $url );
		$response = "$caption " . $this->getConfig( "arrow" ) . " $url";
		return $response;
	}

	protected function resolveInterwiki( $pagename, &$wiki, &$realpagename ) {
		$matches = array();
		if (
			!preg_match( $this->interwikiPattern, $pagename, $matches ) ||
			!$this->wikiSupported( strtolower( $matches['wiki'] ) )
		) {
			$wiki = $this->defaultWiki;
			$realpagename = $pagename;
		} else {
			$wiki = $matches['wiki'];
			$realpagename = $matches['pagename'];
		}
		return true;
	}

	protected function resolveNamespace( $pagename, $defaultNs, &$ns, &$realpagename ) {
		$matches = array();
		if ( preg_match( $this->interwikiPattern, $pagename, $matches ) ) {
			$ns = $matches['wiki'];
			$realpagename = $matches['pagename'];
		} else {
			$ns = $defaultNs;
			$realpagename = $pagename;
		}
		return true;
	}

	protected function getConfig( $key ) {
		return $this->config[$key];
	}

	protected function cleanPageName( $pagename ) {
		return str_replace( "_", " ", $pagename );
	}

	protected function encodePageName( $pagename ) {
		return str_replace( " ", "_", $pagename );
	}

	protected function wikiSupported( $wiki ) {
		return in_array( $wiki, $this->cacheInterwikiList );
	}

	protected function apiSupported( $wiki ) {
		return !empty( $this->cacheInterwikiApiMap[$wiki] );
	}
}
