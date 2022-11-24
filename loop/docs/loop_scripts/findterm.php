<?php

require_once '../includes/WebStart.php';

use MediaWiki\MediaWikiServices;

$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
$linkRenderer->setForceArticlePath(true);

global $wgOut;

$term = $_REQUEST["term"];

$user = $wgOut->getUser();
if ( !$user->isLoggedIn() ) {
    $title = Title::newFromText("Special:Login");
    echo $linkRenderer->makelink( 
        $title,
        new HtmlArmor( $title->mTextform )
    );
    exit;
}

if ( empty ( $term ) ) {
    echo "No search term given";
    exit;
}

$dbr = wfGetDB( DB_REPLICA );
$res = $dbr->select(
    array(
        'page'
    ),
    array(
        'page_id',
        'page_namespace'
    ),
    array(
        'page_namespace = 0'
    ),
    __METHOD__
);
$html = "";
foreach( $res as $row ) {
    $title = Title::newFromId( $row->page_id, NS_MAIN );
    $tmpFPage = new FlaggableWikiPage ( Title::newFromId( $row->page_id, NS_MAIN ) );
    $stableRev = $tmpFPage->getStable();
    if ( $stableRev == 0 ) {
        $stableRev = $tmpFPage->getRevision()->getId();
    } 
    
    $latestRevId = $title->getLatestRevID();
    $wikiPage = WikiPage::factory( $title );
    $fwp = new FlaggableWikiPage ( $title );
    
    
    if ( isset( $fwp ) ) {
        $stableRevId = $fwp->getStable();
        if ( $stableRevId == null ) { # page is stable or does not have any stable version
            $contentText = $wikiPage->getContent()->getText(); #latest, but not stable
        } else {
            $revision = $wikiPage->getRevision();
            $contentText = $revision->getContent()->getText();
        }

        preg_match_all("/($term)/i", $contentText, $output_array); #br with id

        if ( !empty( $output_array[0] ) ) {

			$html .= $linkRenderer->makelink( 
				$title,
				new HtmlArmor( $title->mTextform ),
                array(),
                array("action"=>"edit")
            )."<br>";
            
        }
    }

        
}
echo ($html);