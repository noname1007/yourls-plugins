<?php
/*
Plugin Name: Fake_link_IP
Plugin URI: https://github.com/noname1007/Fake_link_IP
Description: Plugin Fake_link_IP with blacklisted IPs
Version: 1.3
Author: noname
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

include "noname_fake_link_Check_IP_Module.php";

// Hook the custom function into the 'pre_check_ip_flood' event
yourls_add_action( 'redirect_shorturl', 'noname_fake_link_ip_root' );

// Hook the admin page into the 'plugins_loaded' event
yourls_add_action( 'plugins_loaded', 'noname_fake_link_ip_add_page' );

// Get blacklisted IPs from YOURLS options feature and compare with current IP address
function noname_fake_link_ip_root ($args) {
	$IP = yourls_get_IP();
    include "is_bot.php";
	if (is_bot($_SERVER['HTTP_USER_AGENT'])){
    	$fake = yourls_get_keyword_title ($args[1]);
    	yourls_redirect( $fake, 301 );
    	die();
    }
	$Intervalle_IP = yourls_get_option ('noname_fake_link_ip_lists');
	$Intervalle_IP = ( $Intervalle_IP ) ? ( unserialize ( $Intervalle_IP ) ):((array)NULL); 
	foreach ( $Intervalle_IP as $value ) {
		$IPs = explode ( "-" , $value );
    
		if ((ip2long($IP) >= ip2long($IPs[0]) AND ip2long($IP) <= ip2long($IPs[1])) or filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$fake = yourls_get_keyword_title ($args[1]);
			yourls_redirect( $fake, 301 );
			die();
		}
    }
}
// Add admin page
function noname_fake_link_ip_add_page () {
    yourls_register_plugin_page( 'noname_fake_link_ip', 'Fake Link IPs', 'noname_fake_link_ip_do_page' );
}

// Display admin page
function noname_fake_link_ip_do_page () {
    if( isset( $_POST['action'] ) && $_POST['action'] == 'blacklist_ip' ) {
        noname_fake_link_ip_process ();
    } else {
        noname_fake_link_ip_form ();
    }
}

// Display form to administrate blacklisted IPs list
function noname_fake_link_ip_form () {
    $nonce = yourls_create_nonce( 'blacklist_ip' ) ;
    $liste_ip = yourls_get_option ('noname_fake_link_ip_lists','Enter IP addresses here, one entry per line');
    if ($liste_ip != 'Enter IP addresses here, one entry per line' )
        $liste_ip_display = implode ( "\r\n" , unserialize ( $liste_ip ) );
	else
		$liste_ip_display=$liste_ip;
    echo <<<HTML
        <h2>BlackList IPs</h2>
        <form method="post">
        
        <input type="hidden" name="action" value="blacklist_ip" />
        <input type="hidden" name="nonce" value="$nonce" />
        
        <p>Blacklist following IPs (one range or IP per line, no wildcards allowed) :</p>
        <p><textarea cols="50" rows="10" name="blacklist_form">$liste_ip_display</textarea></p>
        
        <p><input type="submit" value="Save" /></p>
		<p>I suggest to add here IPs that you saw adding bulk URL. It is your own responsibility to check the use of the IPs you block. WARNING : erroneous entries may create unexpected behaviours, please double-check before validation.</p>
		<p>Examples : 
			<ul>
				<li>10.0.0.1/24 : blacklist from 10.0.0.0 to 10.0.0.255 (CIDR notation).</li>
				<li>192.168.1.2/255.255.255.128 : blacklist from 192.168.1.0 to 192.168.0.128.</li>
				<li>192.168.1.12-192.168.1.59 : blacklist from 192.168.1.12 to 192.168.1.59.</li>
				<li>192.168.0.0 : blacklist from 192.168.0.0 to 192.168.255.255</li>
				<li>10.0.0.58 : blacklist only 10.0.0.58 IP address.</li>
			</ul>
		</p>
        </form>
HTML;
}

// Update blacklisted IPs list
function noname_fake_link_ip_process () {
    // Check nonce
    yourls_verify_nonce( 'blacklist_ip' ) ;
	
	// Check if the answer is correct.
	$IP_Form = explode ( "\r\n" , $_POST['blacklist_form'] ) ;
	
	if (! is_array ($IP_Form) ) {
		echo "Bad answer, Blacklist not updated";
		die ();
	}
	
	$boucle = 0;

	foreach ($IP_Form as $value) {
		$Retour = noname_fake_link_ip_Analyze_IP ( $value ) ;
		if ( $Retour != "NULL" ) {
			$IPList[$boucle++] = $Retour ;
		}
	}
	// Update list
	yourls_update_option ( 'noname_fake_link_ip_lists', serialize ( $IPList ) );
	echo "Black list updated. New blacklist is " ;
	if ( count ( $IPList ) == 0 ) 
		echo "empty.";
	else {
		echo ":<BR />";
		foreach ($IPList as $value) echo $value."<BR />";
	}
}



