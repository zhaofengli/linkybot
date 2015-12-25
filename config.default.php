<?php
// Please put your own configurations in config.php
$config = array(
	"key" => "Put Telegram Bot API key here",
	"admins" => array( "Put ops' IDs in this array" ),
	"preserveChatPreferences" => true,
	"chatPreferencesFile" => __DIR__ . "/chatPreferences.json",
	"defaultWiki" => "en",
	"defaultLanguage" => "en",

	// For reference, see https://meta.wikimedia.org/wiki/Interwiki_map
	"interwikiMap" => array(
		"wikipedia" => array(
			"pattern" => "https://%wiki%.wikipedia.org/wiki/%pagename%",
			"api" => "https://%wiki%.wikipedia.org/w/api.php",
			"wikis" => array( "en", array( "wikipedia", "en" ), array( "w", "en" ), "zh", "zh-classical", "zh-yue", "fr", "de", "bn", "ja", "beta" )
		),
		"wikimedia" => array(
			"pattern" => "https://%wiki%.wikimedia.org/wiki/%pagename%",
			"api" => "https://%wiki%.wikimedia.org/w/api.php",
			"wikis" => array(
				"meta", array( "m", "meta" ),
				"commons",
				"wikitech",
				"strategy",
				"advisory",
				"donate",
			)
		),
		"mediawiki" => array(
			"pattern" => "https://www.mediawiki.org/wiki/%pagename%",
			"api" => "https://www.mediawiki.org/w/api.php",
			"wikis" => array( "mw" )
		),
		"wiktionary" => array(
			"pattern" => "https://%wiki%.wiktionary.org",
			"api" => "https://%wiki%.wiktionary.org/w/api.php",
			"wikis" => array( array( "wikt", "%defaultLanguage%" ) )
		),
		"wikidata" => array(
			"pattern" => "https://www.wikidata.org/wiki/%pagename%",
			"wikis" => array( "wd", "wikidata" )
		),
		"wmf" => array(
			"pattern" => "https://wikimediafoundation.org/wiki/%pagename%",
			"wikis" => array( "wmf" )
		),
		"toollabs" => array(
			"pattern" => "https://tools.wmflabs.org/%pagename%",
			"wikis" => array( "toollabs" )
		),
		"phabricator" => array(
			"pattern" => "https://phabricator.wikimedia.org/%pagename%",
			"wikis" => array( "phab", "phabricator" )
		),
		"gerrit" => array(
			"pattern" => "https://gerrit.wikimedia.org/r/%pagename%",
			"wikis" => array( "gerrit" )
		),
		"TVtropes" => array(
			"pattern" => "http://www.tvtropes.org/pmwiki/pmwiki.php/Main/%pagename%",
			"wikis" => array( "tvtropes" )
		),
		"wikia" => array(
			"pattern" => "http://www.wikia.com/wiki/c:%pagename%",
			"wikis" => array( "wikia", "wikiasite" )
		),
		"google" => array(
			"pattern" => "https://www.google.com.sg/search?q=%pagename%",
			"wikis" => array( "google" )
		),
		"wenquanyi" => array(
			"pattern" => "http://wqy.sourceforge.net/cgi-bin/index.cgi?%pagename%",
			"wikis" => array( "wqy" )
		),
		"viaf" => array(
			"pattern" => "http://viaf.org/viaf/%pagename%",
			"wikis" => array( "viaf" )
		),
		"freenode" => array(
			"pattern" => "http://kiwiirc.com/client/chat.freenode.net/%pagename%",
			"wikis" => array( "freenode", "irc" )
		),
		"github" => array(
			"pattern" => "https://github.com/%pagename%",
			"wikis" => array( "github" )
		)
	),
	"arrow" => "👉",
	"captions" => array(
		"gender-male" => "👨",
		"gender-female" => "👩",
		"gender-unknown" => "😃",
		"groups-sysop" => "🔧",
		"groups-rollback" => "⏪",
		"apifailed" => "💻❓"
	),
	"captionOverrides" => array(
		"doge" => "🐶",
		"bilibili" => "📺",
		"youtube" => "📺",
		"ingress" => "💣",
		"help:magic words" => "✨❓",
		"user:jimbo wales" => "😎",
		"user talk:jimbo wales" => "😎💬",
		"user:cluebot ng" => "🤖",
		"template:reply to" => "🛎",
		"template:ping" => "🛎",
		"template:delete" => "🗑",
		"template:db" => "🗑",
		"template:d" => "🗑",
		"template:cite web" => "📝🌎",
		"template:cite av media" => "📝🎬",
		"template:cite journal" => "📝📄"
	),
	"magicwords" => array(
		"PAGENAME",
		"NAMESPACE",
		"NAMESPACENUMBER",
		"FULLPAGENAME",
		"BASEPAGENAME",
		"ROOTPAGENAME",
		"SUBPAGENAME",
		"ARTICLEPAGENAME",
		"SUBJECTPAGENAME",
		"ARTICLESPACE",
		"SUBJECTSPACE",
		"TALKPAGENAME",
		"TALKSPACE"
	)
);

@include( __DIR__ . "/config.php" );
