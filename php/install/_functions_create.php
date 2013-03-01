<?php
/**
 * This file implements creation of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}
 * Parts of this file are copyright (c)2005 by Jason EDGECOMBE
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Jason EDGECOMBE grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Matt FOLLETT grants Francois PLANQUE the right to license
 * Matt FOLLETT contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package install
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author vegarg: Vegar BERG GULDAL.
 * @author edgester: Jason EDGECOMBE.
 * @author mfollett: Matt Follett.
 *
 * @version $Id: _functions_create.php 1559 2012-07-20 08:25:25Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_group.class.php', 'Group' );
load_funcs( 'collections/model/_category.funcs.php' );

/**
 * Used for fresh install
 */
function create_tables()
{
	global $inc_path;

	// Load DB schema from modules
	load_db_schema();

	load_funcs('_core/model/db/_upgrade.funcs.php');

	// Alter DB to match DB schema:
	install_make_db_schema_current( true );
}


/**
 * Insert all default data:
 */
function create_default_data()
{
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $DB, $locales, $current_locale;

	// Inserting sample data triggers events: instead of checking if $Plugins is an object there, just use a fake one..
	load_class('plugins/model/_plugins_admin_no_db.class.php', 'Plugins_admin_no_DB' );
	global $Plugins;
	$Plugins = new Plugins_admin_no_DB(); // COPY

	// added in 0.8.7
	echo 'Creating default blacklist entries... ';
	// This string contains antispam information that is obfuscated because some hosting
	// companies prevent uploading PHP files containing "spam" strings.
	// pre_dump(get_antispam_query());
	$query = get_antispam_query();
	$DB->query( $query );
	echo "OK.<br />\n";


	// added in 0.8.9
	echo 'Creating default groups... ';
	$Group_Admins = new Group(); // COPY !
	$Group_Admins->set( 'name', 'Administrators' );
	$Group_Admins->set( 'perm_blogs', 'editall' );
	$Group_Admins->set( 'perm_stats', 'edit' );
	$Group_Admins->set( 'perm_users', 'edit' );
	$Group_Admins->set( 'perm_xhtml_css_tweaks', 1 );
	$Group_Admins->dbinsert();

	$Group_Privileged = new Group(); // COPY !
	$Group_Privileged->set( 'name', 'Privileged Bloggers' );
	$Group_Privileged->set( 'perm_blogs', 'viewall' );
	$Group_Privileged->set( 'perm_stats', 'user' );
	$Group_Privileged->set( 'perm_users', 'view' );
	$Group_Privileged->set( 'perm_xhtml_css_tweaks', 1 );
	$Group_Privileged->dbinsert();

	$Group_Bloggers = new Group(); // COPY !
	$Group_Bloggers->set( 'name', 'Bloggers' );
	$Group_Bloggers->set( 'perm_blogs', 'user' );
	$Group_Bloggers->set( 'perm_stats', 'none' );
	$Group_Bloggers->set( 'perm_users', 'none' );
	$Group_Bloggers->set( 'perm_xhtml_css_tweaks', 1 );
	$Group_Bloggers->dbinsert();

	$Group_Users = new Group(); // COPY !
	$Group_Users->set( 'name', 'Basic Users' );
	$Group_Users->set( 'perm_blogs', 'user' );
	$Group_Users->set( 'perm_stats', 'none' );
	$Group_Users->set( 'perm_users', 'none' );
	$Group_Users->dbinsert();
	echo "OK.<br />\n";

	echo 'Creating user field definitions... ';
	// fp> Anyone, please add anything you can think of. It's better to start with a large list that update it progressively.
	$DB->query( "
    INSERT INTO T_users__fielddefs (ufdf_ID, ufdf_type, ufdf_name)
		 VALUES ( 10000, 'email',    'MSN/Live IM'),
						( 10100, 'word',     'Yahoo IM'),
						( 10200, 'word',     'AOL AIM'),
						( 10300, 'number',   'ICQ ID'),
						( 40000, 'phone',    'Skype'),
						( 50000, 'phone',    'Main phone'),
						( 50100, 'phone',    'Cell phone'),
						( 50200, 'phone',    'Office phone'),
						( 50300, 'phone',    'Home phone'),
						( 60000, 'phone',    'Office FAX'),
						( 60100, 'phone',    'Home FAX'),
						(100000, 'url',      'Website'),
						(100100, 'url',      'Blog'),
						(110000, 'url',      'Linkedin'),
						(120000, 'url',      'Twitter'),
						(130100, 'url',      'Facebook'),
						(130200, 'url',      'Myspace'),
						(140000, 'url',      'Flickr'),
						(150000, 'url',      'YouTube'),
						(160000, 'url',      'Digg'),
						(160100, 'url',      'StumbleUpon'),
						(200000, 'text',     'Role'),
						(200100, 'text',     'Organization'),
						(200200, 'text',     'Division'),
						(211000, 'text',     'VAT ID'),
						(300000, 'text',     'Main address'),
						(300300, 'text',     'Home address');" );
	echo "OK.<br />\n";


	echo 'Creating admin user... ';
	global $timestamp, $admin_email, $default_locale, $install_password;
	global $random_password;

	$User_Admin = new User();
	$User_Admin->set( 'login', 'admin' );
	if( !isset( $install_password ) )
	{
		$random_password = generate_random_passwd(); // no ambiguous chars
	}
	else
	{
		$random_password = $install_password;
	}
	$User_Admin->set( 'pass', md5($random_password) );	// random
	$User_Admin->set( 'nickname', 'admin' );
	$User_Admin->set_email( $admin_email );
	$User_Admin->set( 'validated', 1 ); // assume it's validated
	$User_Admin->set( 'ip', '127.0.0.1' );
	$User_Admin->set( 'domain', 'localhost' );
	$User_Admin->set( 'level', 10 );
	$User_Admin->set( 'locale', $default_locale );
	$User_Admin->set_datecreated( $timestamp++ );
	// Note: NEVER use database time (may be out of sync + no TZ control)
	$User_Admin->set_Group( $Group_Admins );
	$User_Admin->dbinsert();
	echo "OK.<br />\n";

	// Activating multiple sessions for administrator
	echo 'Activating multiple sessions for administrator... ';
	$DB->query( "
		INSERT INTO T_users__usersettings ( uset_user_ID, uset_name, uset_value )
		VALUES ( 1, 'login_multiple_sessions', '1' )" );
	echo "OK.<br />\n";


	// added in Phoenix-Alpha
	echo 'Creating default Post Types... ';
	$DB->query( "
		INSERT INTO T_items__type ( ptyp_ID, ptyp_name )
		VALUES ( 1, 'Post' ),
					 ( 1000, 'Page' ),
					 ( 1500, 'Intro-Main' ),
					 ( 1520, 'Intro-Cat' ),
					 ( 1530, 'Intro-Tag' ),
					 ( 1570, 'Intro-Sub' ),
					 ( 1600, 'Intro-All' ),
					 ( 2000, 'Podcast' ),
					 ( 3000, 'Sidebar link' ),
					 ( 4000, 'Reserved' ),
					 ( 5000, 'Reserved' ) " );
	echo "OK.<br />\n";


	// added in Phoenix-Beta
	echo 'Creating default file types... ';
	// Contribs: feel free to add more types here...
	// TODO: dh> shouldn't they get localized to the app's default locale? fp> ftyp_name, yes
	$DB->query( "INSERT INTO T_filetypes
			(ftyp_ID, ftyp_extensions, ftyp_name, ftyp_mimetype, ftyp_icon, ftyp_viewtype, ftyp_allowed)
		VALUES
			(1, 'gif', 'GIF image', 'image/gif', 'image2.png', 'image', 'any'),
			(2, 'png', 'PNG image', 'image/png', 'image2.png', 'image', 'any'),
			(3, 'jpg jpeg', 'JPEG image', 'image/jpeg', 'image2.png', 'image', 'any'),
			(4, 'txt', 'Text file', 'text/plain', 'document.png', 'text', 'registered'),
			(5, 'htm html', 'HTML file', 'text/html', 'html.png', 'browser', 'admin'),
			(6, 'pdf', 'PDF file', 'application/pdf', 'pdf.png', 'browser', 'registered'),
			(7, 'doc', 'Microsoft Word file', 'application/msword', 'doc.gif', 'external', 'registered'),
			(8, 'xls', 'Microsoft Excel file', 'application/vnd.ms-excel', 'xls.gif', 'external', 'registered'),
			(9, 'ppt', 'Powerpoint', 'application/vnd.ms-powerpoint', 'ppt.gif', 'external', 'registered'),
			(10, 'pps', 'Slideshow', 'pps', 'pps.gif', 'external', 'registered'),
			(11, 'zip', 'ZIP archive', 'application/zip', 'zip.gif', 'external', 'registered'),
			(12, 'php php3 php4 php5 php6', 'PHP script', 'application/x-httpd-php', 'php.gif', 'text', 'admin'),
			(13, 'css', 'Style sheet', 'text/css', '', 'text', 'registered'),
			(14, 'mp3', 'MPEG audio file', 'audio/mpeg', '', 'browser', 'registered'),
			(15, 'm4a', 'MPEG audio file', 'audio/x-m4a', '', 'browser', 'registered'),
			(16, 'mp4', 'MPEG video', 'video/mp4', '', 'browser', 'registered'),
			(17, 'mov', 'Quicktime video', 'video/quicktime', '', 'browser', 'registered'),
			(18, 'm4v', 'MPEG video file', 'video/x-m4v', '', 'browser', 'registered')
		" );
	echo "OK.<br />\n";

	if( ! empty( $current_locale ) )
	{	// Make sure the user sees his new system localized.
		echo 'Activating selected default locale... ';
		$DB->query( 'INSERT INTO T_locales '
				   .'( loc_locale, loc_charset, loc_datefmt, loc_timefmt, '
				   .'loc_startofweek, loc_name, loc_messages, loc_priority, '
				   .'loc_enabled ) '
				   .'VALUES ( '.$DB->quote( $current_locale ).', '
				   .$DB->quote( $locales[$current_locale]['charset'] ).', '
				   .$DB->quote( $locales[$current_locale]['datefmt'] ).', '
				   .$DB->quote( $locales[$current_locale]['timefmt'] ).', '
				   .$DB->quote( $locales[$current_locale]['startofweek'] ).', '
				   .$DB->quote( $locales[$current_locale]['name'] ).', '
				   .$DB->quote( $locales[$current_locale]['messages'] ).', '
				   .$DB->quote( $locales[$current_locale]['priority'] ).', '
				   .' 1)' );
		echo 'OK.<br />', "\n";
	}

	create_default_settings();

	// don't change order of the following two functions as countries has relations to currencies
	create_default_currencies();
	create_default_countries();

	// create default scheduled jobs
	create_default_jobs();

	echo 'Creating default "help" slug... ';
	$DB->query( '
		INSERT INTO T_slug( slug_title, slug_type )
		VALUES( "help", "help" )', 'Add "help" slug' );
	echo "OK.<br />\n";

	install_basic_skins();

	install_basic_plugins();

	return true;
}

/**
 * Create default currencies
 *
 */
function create_default_currencies()
{
	global $DB;

	echo 'Creating default currencies... ';
	$DB->query( "
    INSERT INTO T_currency (curr_ID, curr_code, curr_shortcut, curr_name)
		 VALUES
			(1, 'AFN', '&#x60b;', 'Afghani'),
			(2, 'EUR', '&euro;', 'Euro'),
			(3, 'ALL', 'Lek', 'Lek'),
			(4, 'DZD', 'DZD', 'Algerian Dinar'),
			(5, 'USD', '$', 'US Dollar'),
			(6, 'AOA', 'AOA', 'Kwanza'),
			(7, 'XCD', '$', 'East Caribbean Dollar'),
			(8, 'ARS', '$', 'Argentine Peso'),
			(9, 'AMD', 'AMD', 'Armenian Dram'),
			(10, 'AWG', '&fnof;', 'Aruban Guilder'),
			(11, 'AUD', '$', 'Australian Dollar'),
			(12, 'AZN', '&#x43c;&#x430;&#x43d;', 'Azerbaijanian Manat'),
			(13, 'BSD', '$', 'Bahamian Dollar'),
			(14, 'BHD', 'BHD', 'Bahraini Dinar'),
			(15, 'BDT', 'BDT', 'Taka'),
			(16, 'BBD', '$', 'Barbados Dollar'),
			(17, 'BYR', 'p.', 'Belarussian Ruble'),
			(18, 'BZD', 'BZ$', 'Belize Dollar'),
			(19, 'XOF', 'XOF', 'CFA Franc BCEAO'),
			(20, 'BMD', '$', 'Bermudian Dollar'),
			(21, 'BAM', 'KM', 'Convertible Marks'),
			(22, 'BWP', 'P', 'Pula'),
			(23, 'NOK', 'kr', 'Norwegian Krone'),
			(24, 'BRL', 'R$', 'Brazilian Real'),
			(25, 'BND', '$', 'Brunei Dollar'),
			(26, 'BGN', '&#x43b;&#x432;', 'Bulgarian Lev'),
			(27, 'BIF', 'BIF', 'Burundi Franc'),
			(28, 'KHR', '&#x17db;', 'Riel'),
			(29, 'XAF', 'XAF', 'CFA Franc BEAC'),
			(30, 'CAD', '$', 'Canadian Dollar'),
			(31, 'CVE', 'CVE', 'Cape Verde Escudo'),
			(32, 'KYD', '$', 'Cayman Islands Dollar'),
			(33, 'CNY', '&yen;', 'Yuan Renminbi'),
			(34, 'KMF', 'KMF', 'Comoro Franc'),
			(35, 'CDF', 'CDF', 'Congolese Franc'),
			(36, 'NZD', '$', 'New Zealand Dollar'),
			(37, 'CRC', '&#x20a1;', 'Costa Rican Colon'),
			(38, 'HRK', 'kn', 'Croatian Kuna'),
			(39, 'CZK', 'K&#x10d;', 'Czech Koruna'),
			(40, 'DKK', 'kr', 'Danish Krone'),
			(41, 'DJF', 'DJF', 'Djibouti Franc'),
			(42, 'DOP', 'RD$', 'Dominican Peso'),
			(43, 'EGP', '&pound;', 'Egyptian Pound'),
			(44, 'ERN', 'ERN', 'Nakfa'),
			(45, 'EEK', 'EEK', 'Kroon'),
			(46, 'ETB', 'ETB', 'Ethiopian Birr'),
			(47, 'FKP', '&pound;', 'Falkland Islands Pound'),
			(48, 'FJD', '$', 'Fiji Dollar'),
			(49, 'XPF', 'XPF', 'CFP Franc'),
			(50, 'GMD', 'GMD', 'Dalasi'),
			(51, 'GEL', 'GEL', 'Lari'),
			(52, 'GHS', 'GHS', 'Cedi'),
			(53, 'GIP', '&pound;', 'Gibraltar Pound'),
			(54, 'GTQ', 'Q', 'Quetzal'),
			(55, 'GBP', '&pound;', 'Pound Sterling'),
			(56, 'GNF', 'GNF', 'Guinea Franc'),
			(57, 'GYD', '$', 'Guyana Dollar'),
			(58, 'HNL', 'L', 'Lempira'),
			(59, 'HKD', '$', 'Hong Kong Dollar'),
			(60, 'HUF', 'Ft', 'Forint'),
			(61, 'ISK', 'kr', 'Iceland Krona'),
			(62, 'INR', 'Rs', 'Indian Rupee'),
			(63, 'IDR', 'Rp', 'Rupiah'),
			(64, 'IRR', '&#xfdfc;', 'Iranian Rial'),
			(65, 'IQD', 'IQD', 'Iraqi Dinar'),
			(66, 'ILS', '&#x20aa;', 'New Israeli Sheqel'),
			(67, 'JMD', 'J$', 'Jamaican Dollar'),
			(68, 'JPY', '&yen;', 'Yen'),
			(69, 'JOD', 'JOD', 'Jordanian Dinar'),
			(70, 'KZT', '&#x43b;&#x432;', 'Tenge'),
			(71, 'KES', 'KES', 'Kenyan Shilling'),
			(72, 'KPW', '&#x20a9;', 'North Korean Won'),
			(73, 'KRW', '&#x20a9;', 'Won'),
			(74, 'KWD', 'KWD', 'Kuwaiti Dinar'),
			(75, 'KGS', '&#x43b;&#x432;', 'Som'),
			(76, 'LAK', '&#x20ad;', 'Kip'),
			(77, 'LVL', 'Ls', 'Latvian Lats'),
			(78, 'LBP', '&pound;', 'Lebanese Pound'),
			(79, 'LRD', '$', 'Liberian Dollar'),
			(80, 'LYD', 'LYD', 'Libyan Dinar'),
			(81, 'CHF', 'CHF', 'Swiss Franc'),
			(82, 'LTL', 'Lt', 'Lithuanian Litas'),
			(83, 'MOP', 'MOP', 'Pataca'),
			(84, 'MKD', '&#x434;&#x435;&#x43d;', 'Denar'),
			(85, 'MGA', 'MGA', 'Malagasy Ariary'),
			(86, 'MWK', 'MWK', 'Kwacha'),
			(87, 'MYR', 'RM', 'Malaysian Ringgit'),
			(88, 'MVR', 'MVR', 'Rufiyaa'),
			(89, 'MRO', 'MRO', 'Ouguiya'),
			(90, 'MUR', 'Rs', 'Mauritius Rupee'),
			(91, 'MDL', 'MDL', 'Moldovan Leu'),
			(92, 'MNT', '&#x20ae;', 'Tugrik'),
			(93, 'MAD', 'MAD', 'Moroccan Dirham'),
			(94, 'MZN', 'MT', 'Metical'),
			(95, 'MMK', 'MMK', 'Kyat'),
			(96, 'NPR', 'Rs', 'Nepalese Rupee'),
			(97, 'ANG', '&fnof;', 'Netherlands Antillian Guilder'),
			(98, 'NIO', 'C$', 'Cordoba Oro'),
			(99, 'NGN', '&#x20a6;', 'Naira'),
			(100, 'OMR', '&#xfdfc;', 'Rial Omani'),
			(101, 'PKR', 'Rs', 'Pakistan Rupee'),
			(102, 'PGK', 'PGK', 'Kina'),
			(103, 'PYG', 'Gs', 'Guarani'),
			(104, 'PEN', 'S/.', 'Nuevo Sol'),
			(105, 'PHP', 'Php', 'Philippine Peso'),
			(106, 'PLN', 'z&#x142;', 'Zloty'),
			(107, 'QAR', '&#xfdfc;', 'Qatari Rial'),
			(108, 'RON', 'lei', 'New Leu'),
			(109, 'RUB', '&#x440;&#x443;&#x431;', 'Russian Ruble'),
			(110, 'RWF', 'RWF', 'Rwanda Franc'),
			(111, 'SHP', '&pound;', 'Saint Helena Pound'),
			(112, 'WST', 'WST', 'Tala'),
			(113, 'STD', 'STD', 'Dobra'),
			(114, 'SAR', '&#xfdfc;', 'Saudi Riyal'),
			(115, 'RSD', '&#x414;&#x438;&#x43d;.', 'Serbian Dinar'),
			(116, 'SCR', 'Rs', 'Seychelles Rupee'),
			(117, 'SLL', 'SLL', 'Leone'),
			(118, 'SGD', '$', 'Singapore Dollar'),
			(119, 'SBD', '$', 'Solomon Islands Dollar'),
			(120, 'SOS', 'S', 'Somali Shilling'),
			(121, 'ZAR', 'R', 'Rand'),
			(122, 'LKR', 'Rs', 'Sri Lanka Rupee'),
			(123, 'SDG', 'SDG', 'Sudanese Pound'),
			(124, 'SRD', '$', 'Surinam Dollar'),
			(125, 'SZL', 'SZL', 'Lilangeni'),
			(126, 'SEK', 'kr', 'Swedish Krona'),
			(127, 'SYP', '&pound;', 'Syrian Pound'),
			(128, 'TWD', '$', 'New Taiwan Dollar'),
			(129, 'TJS', 'TJS', 'Somoni'),
			(130, 'TZS', 'TZS', 'Tanzanian Shilling'),
			(131, 'THB', 'THB', 'Baht'),
			(132, 'TOP', 'TOP', 'Pa'),
			(133, 'TTD', 'TT$', 'Trinidad and Tobago Dollar'),
			(134, 'TND', 'TND', 'Tunisian Dinar'),
			(135, 'TRY', 'TL', 'Turkish Lira'),
			(136, 'TMT', 'TMT', 'Manat'),
			(137, 'UGX', 'UGX', 'Uganda Shilling'),
			(138, 'UAH', '&#x20b4;', 'Hryvnia'),
			(139, 'AED', 'AED', 'UAE Dirham'),
			(140, 'UZS', '&#x43b;&#x432;', 'Uzbekistan Sum'),
			(141, 'VUV', 'VUV', 'Vatu'),
			(142, 'VEF', 'Bs', 'Bolivar Fuerte'),
			(143, 'VND', '&#x20ab;', 'Dong'),
			(144, 'YER', '&#xfdfc;', 'Yemeni Rial'),
			(145, 'ZMK', 'ZMK', 'Zambian Kwacha'),
			(146, 'ZWL', 'Z$', 'Zimbabwe Dollar'),
			(147, 'XAU', 'XAU', 'Gold'),
			(148, 'XBA', 'XBA', 'EURCO'),
			(149, 'XBB', 'XBB', 'European Monetary Unit'),
			(150, 'XBC', 'XBC', 'European Unit of Account 9'),
			(151, 'XBD', 'XBD', 'European Unit of Account 17'),
			(152, 'XDR', 'XDR', 'SDR'),
			(153, 'XPD', 'XPD', 'Palladium'),
			(154, 'XPT', 'XPT', 'Platinum'),
			(155, 'XAG', 'XAG', 'Silver'),
			(156, 'COP', '$', 'Colombian peso'),
			(157, 'CUP', '$', 'Cuban peso'),
			(158, 'SVC', 'SVC', 'Salvadoran colon'),
			(159, 'CLP', '$', 'Chilean peso'),
			(160, 'HTG', 'G', 'Haitian gourde'),
			(161, 'MXN', '$', 'Mexican peso'),
			(162, 'PAB', 'PAB', 'Panamanian balboa'),
			(163, 'UYU', '$', 'Uruguayan peso')
			" );
	echo "OK.<br />\n";
}

/**
 * Create default countries with relations to currencies
 *
 */
function create_default_countries()
{
	global $DB;

	echo 'Creating default countries... ';
	$DB->query( "
		INSERT INTO T_country ( ctry_ID, ctry_code, ctry_name, ctry_curr_ID)
		VALUES
			(1, 'af', 'Afghanistan', 1),
			(2, 'ax', 'Aland Islands', 2),
			(3, 'al', 'Albania', 3),
			(4, 'dz', 'Algeria', 4),
			(5, 'as', 'American Samoa', 5),
			(6, 'ad', 'Andorra', 2),
			(7, 'ao', 'Angola', 6),
			(8, 'ai', 'Anguilla', 7),
			(9, 'aq', 'Antarctica', NULL),
			(10, 'ag', 'Antigua And Barbuda', 7),
			(11, 'ar', 'Argentina', 8),
			(12, 'am', 'Armenia', 9),
			(13, 'aw', 'Aruba', 10),
			(14, 'au', 'Australia', 11),
			(15, 'at', 'Austria', 2),
			(16, 'az', 'Azerbaijan', 12),
			(17, 'bs', 'Bahamas', 13),
			(18, 'bh', 'Bahrain', 14),
			(19, 'bd', 'Bangladesh', 15),
			(20, 'bb', 'Barbados', 16),
			(21, 'by', 'Belarus', 17),
			(22, 'be', 'Belgium', 2),
			(23, 'bz', 'Belize', 18),
			(24, 'bj', 'Benin', 19),
			(25, 'bm', 'Bermuda', 20),
			(26, 'bt', 'Bhutan', 62),
			(27, 'bo', 'Bolivia', NULL),
			(28, 'ba', 'Bosnia And Herzegovina', 21),
			(29, 'bw', 'Botswana', 22),
			(30, 'bv', 'Bouvet Island', 23),
			(31, 'br', 'Brazil', 24),
			(32, 'io', 'British Indian Ocean Territory', 5),
			(33, 'bn', 'Brunei Darussalam', 25),
			(34, 'bg', 'Bulgaria', 26),
			(35, 'bf', 'Burkina Faso', 19),
			(36, 'bi', 'Burundi', 27),
			(37, 'kh', 'Cambodia', 28),
			(38, 'cm', 'Cameroon', 29),
			(39, 'ca', 'Canada', 30),
			(40, 'cv', 'Cape Verde', 31),
			(41, 'ky', 'Cayman Islands', 32),
			(42, 'cf', 'Central African Republic', 29),
			(43, 'td', 'Chad', 29),
			(44, 'cl', 'Chile', 159),
			(45, 'cn', 'China', 33),
			(46, 'cx', 'Christmas Island', 11),
			(47, 'cc', 'Cocos Islands', 11),
			(48, 'co', 'Colombia', 156),
			(49, 'km', 'Comoros', 34),
			(50, 'cg', 'Congo', 29),
			(51, 'cd', 'Congo Republic', 35),
			(52, 'ck', 'Cook Islands', 36),
			(53, 'cr', 'Costa Rica', 37),
			(54, 'ci', 'Cote Divoire', 19),
			(55, 'hr', 'Croatia', 38),
			(56, 'cu', 'Cuba', 157),
			(57, 'cy', 'Cyprus', 2),
			(58, 'cz', 'Czech Republic', 39),
			(59, 'dk', 'Denmark', 40),
			(60, 'dj', 'Djibouti', 41),
			(61, 'dm', 'Dominica', 7),
			(62, 'do', 'Dominican Republic', 42),
			(63, 'ec', 'Ecuador', 5),
			(64, 'eg', 'Egypt', 43),
			(65, 'sv', 'El Salvador', 158),
			(66, 'gq', 'Equatorial Guinea', 29),
			(67, 'er', 'Eritrea', 44),
			(68, 'ee', 'Estonia', 45),
			(69, 'et', 'Ethiopia', 46),
			(70, 'fk', 'Falkland Islands (Malvinas)', 47),
			(71, 'fo', 'Faroe Islands', 40),
			(72, 'fj', 'Fiji', 48),
			(73, 'fi', 'Finland', 2),
			(74, 'fr', 'France', 2),
			(75, 'gf', 'French Guiana', 2),
			(76, 'pf', 'French Polynesia', 49),
			(77, 'tf', 'French Southern Territories', 2),
			(78, 'ga', 'Gabon', 29),
			(79, 'gm', 'Gambia', 50),
			(80, 'ge', 'Georgia', 51),
			(81, 'de', 'Germany', 2),
			(82, 'gh', 'Ghana', 52),
			(83, 'gi', 'Gibraltar', 53),
			(84, 'gr', 'Greece', 2),
			(85, 'gl', 'Greenland', 40),
			(86, 'gd', 'Grenada', 7),
			(87, 'gp', 'Guadeloupe', 2),
			(88, 'gu', 'Guam', 5),
			(89, 'gt', 'Guatemala', 54),
			(90, 'gg', 'Guernsey', 55),
			(91, 'gn', 'Guinea', 56),
			(92, 'gw', 'Guinea-bissau', 19),
			(93, 'gy', 'Guyana', 57),
			(94, 'ht', 'Haiti', 160),
			(95, 'hm', 'Heard Island And Mcdonald Islands', 11),
			(96, 'va', 'Holy See (vatican City State)', 2),
			(97, 'hn', 'Honduras', 58),
			(98, 'hk', 'Hong Kong', 59),
			(99, 'hu', 'Hungary', 60),
			(100, 'is', 'Iceland', 61),
			(101, 'in', 'India', 62),
			(102, 'id', 'Indonesia', 63),
			(103, 'ir', 'Iran', 64),
			(104, 'iq', 'Iraq', 65),
			(105, 'ie', 'Ireland', 2),
			(106, 'im', 'Isle Of Man', NULL),
			(107, 'il', 'Israel', 66),
			(108, 'it', 'Italy', 2),
			(109, 'jm', 'Jamaica', 67),
			(110, 'jp', 'Japan', 68),
			(111, 'je', 'Jersey', 55),
			(112, 'jo', 'Jordan', 69),
			(113, 'kz', 'Kazakhstan', 70),
			(114, 'ke', 'Kenya', 71),
			(115, 'ki', 'Kiribati', 11),
			(116, 'kp', 'Korea', 72),
			(117, 'kr', 'Korea', 73),
			(118, 'kw', 'Kuwait', 74),
			(119, 'kg', 'Kyrgyzstan', 75),
			(120, 'la', 'Lao', 76),
			(121, 'lv', 'Latvia', 77),
			(122, 'lb', 'Lebanon', 78),
			(123, 'ls', 'Lesotho', 121),
			(124, 'lr', 'Liberia', 79),
			(125, 'ly', 'Libyan Arab Jamahiriya', 80),
			(126, 'li', 'Liechtenstein', 81),
			(127, 'lt', 'Lithuania', 82),
			(128, 'lu', 'Luxembourg', 2),
			(129, 'mo', 'Macao', 83),
			(130, 'mk', 'Macedonia', 84),
			(131, 'mg', 'Madagascar', 85),
			(132, 'mw', 'Malawi', 86),
			(133, 'my', 'Malaysia', 87),
			(134, 'mv', 'Maldives', 88),
			(135, 'ml', 'Mali', 19),
			(136, 'mt', 'Malta', 2),
			(137, 'mh', 'Marshall Islands', 5),
			(138, 'mq', 'Martinique', 2),
			(139, 'mr', 'Mauritania', 89),
			(140, 'mu', 'Mauritius', 90),
			(141, 'yt', 'Mayotte', 2),
			(142, 'mx', 'Mexico', 161),
			(143, 'fm', 'Micronesia', 2),
			(144, 'md', 'Moldova', 91),
			(145, 'mc', 'Monaco', 2),
			(146, 'mn', 'Mongolia', 92),
			(147, 'me', 'Montenegro', 2),
			(148, 'ms', 'Montserrat', 7),
			(149, 'ma', 'Morocco', 93),
			(150, 'mz', 'Mozambique', 94),
			(151, 'mm', 'Myanmar', 95),
			(152, 'na', 'Namibia', 121),
			(153, 'nr', 'Nauru', 11),
			(154, 'np', 'Nepal', 96),
			(155, 'nl', 'Netherlands', 2),
			(156, 'an', 'Netherlands Antilles', 97),
			(157, 'nc', 'New Caledonia', 49),
			(158, 'nz', 'New Zealand', 36),
			(159, 'ni', 'Nicaragua', 98),
			(160, 'ne', 'Niger', 19),
			(161, 'ng', 'Nigeria', 99),
			(162, 'nu', 'Niue', 36),
			(163, 'nf', 'Norfolk Island', 11),
			(164, 'mp', 'Northern Mariana Islands', 5),
			(165, 'no', 'Norway', 23),
			(166, 'om', 'Oman', 100),
			(167, 'pk', 'Pakistan', 101),
			(168, 'pw', 'Palau', 5),
			(169, 'ps', 'Palestinian Territory', NULL),
			(170, 'pa', 'Panama', 162),
			(171, 'pg', 'Papua New Guinea', 102),
			(172, 'py', 'Paraguay', 103),
			(173, 'pe', 'Peru', 104),
			(174, 'ph', 'Philippines', 105),
			(175, 'pn', 'Pitcairn', 36),
			(176, 'pl', 'Poland', 106),
			(177, 'pt', 'Portugal', 2),
			(178, 'pr', 'Puerto Rico', 5),
			(179, 'qa', 'Qatar', 107),
			(180, 're', 'Reunion', 2),
			(181, 'ro', 'Romania', 108),
			(182, 'ru', 'Russian Federation', 109),
			(183, 'rw', 'Rwanda', 110),
			(184, 'bl', 'Saint Barthelemy', 2),
			(185, 'sh', 'Saint Helena', 111),
			(186, 'kn', 'Saint Kitts And Nevis', 7),
			(187, 'lc', 'Saint Lucia', 7),
			(188, 'mf', 'Saint Martin', 2),
			(189, 'pm', 'Saint Pierre And Miquelon', 2),
			(190, 'vc', 'Saint Vincent And The Grenadines', 7),
			(191, 'ws', 'Samoa', 112),
			(192, 'sm', 'San Marino', 2),
			(193, 'st', 'Sao Tome And Principe', 113),
			(194, 'sa', 'Saudi Arabia', 114),
			(195, 'sn', 'Senegal', 19),
			(196, 'rs', 'Serbia', 115),
			(197, 'sc', 'Seychelles', 116),
			(198, 'sl', 'Sierra Leone', 117),
			(199, 'sg', 'Singapore', 118),
			(200, 'sk', 'Slovakia', 2),
			(201, 'si', 'Slovenia', 2),
			(202, 'sb', 'Solomon Islands', 119),
			(203, 'so', 'Somalia', 120),
			(204, 'za', 'South Africa', 121),
			(205, 'gs', 'South Georgia', NULL),
			(206, 'es', 'Spain', 2),
			(207, 'lk', 'Sri Lanka', 122),
			(208, 'sd', 'Sudan', 123),
			(209, 'sr', 'Suriname', 124),
			(210, 'sj', 'Svalbard And Jan Mayen', 23),
			(211, 'sz', 'Swaziland', 125),
			(212, 'se', 'Sweden', 126),
			(213, 'ch', 'Switzerland', 81),
			(214, 'sy', 'Syrian Arab Republic', 127),
			(215, 'tw', 'Taiwan, Province Of China', 128),
			(216, 'tj', 'Tajikistan', 129),
			(217, 'tz', 'Tanzania', 130),
			(218, 'th', 'Thailand', 131),
			(219, 'tl', 'Timor-leste', 5),
			(220, 'tg', 'Togo', 19),
			(221, 'tk', 'Tokelau', 36),
			(222, 'to', 'Tonga', 132),
			(223, 'tt', 'Trinidad And Tobago', 133),
			(224, 'tn', 'Tunisia', 134),
			(225, 'tr', 'Turkey', 135),
			(226, 'tm', 'Turkmenistan', 136),
			(227, 'tc', 'Turks And Caicos Islands', 5),
			(228, 'tv', 'Tuvalu', 11),
			(229, 'ug', 'Uganda', 137),
			(230, 'ua', 'Ukraine', 138),
			(231, 'ae', 'United Arab Emirates', 139),
			(232, 'gb', 'United Kingdom', 55),
			(233, 'us', 'United States', 5),
			(234, 'um', 'United States Minor Outlying Islands', 5),
			(235, 'uy', 'Uruguay', 163),
			(236, 'uz', 'Uzbekistan', 140),
			(237, 'vu', 'Vanuatu', 141),
			(239, 've', 'Venezuela', 142),
			(240, 'vn', 'Viet Nam', 143),
			(241, 'vg', 'Virgin Islands, British', 5),
			(242, 'vi', 'Virgin Islands, U.s.', 5),
			(243, 'wf', 'Wallis And Futuna', 49),
			(244, 'eh', 'Western Sahara', 93),
			(245, 'ye', 'Yemen', 144),
			(246, 'zm', 'Zambia', 145),
			(247, 'zw', 'Zimbabwe', 146)" );
	echo "OK.<br />\n";
}

/**
 * Create default scheduled jobs that don't exist yet:
 * - Prune page cache
 * - Prune hit log & session log from stats
 * - Poll antispam blacklist
 *
 * @param boolean true if it's called from the ugrade script, false if it's called from the install script
 */
function create_default_jobs( $is_upgrade = false )
{
	global $DB, $localtimenow;

	// get tomorrow date
	$date = date2mysql( $localtimenow + 86400 );
	$ctsk_params = $DB->quote( 'N;' );

	$prune_pagecache_ctrl = 'cron/jobs/_prune_page_cache.job.php';
	$prune_sessions_ctrl = 'cron/jobs/_prune_hits_sessions.job.php';
	$poll_antispam_ctrl = 'cron/jobs/_antispam_poll.job.php';

	// init insert values
	$prune_pagecache = "( ".$DB->quote( form_date( $date, '02:00:00' ) ).", 86400, ".$DB->quote( T_( 'Prune old files from page cache' ) ).", ".$DB->quote( $prune_pagecache_ctrl ).", ".$ctsk_params." )";
	$prune_sessions = "( ".$DB->quote( form_date( $date, '03:00:00' ) ).", 86400, ".$DB->quote( T_( 'Prune old hits & sessions' ) ).", ".$DB->quote( $prune_sessions_ctrl ).", ".$ctsk_params." )";
	$poll_antispam = "( ".$DB->quote( form_date( $date, '04:00:00' ) ).", 86400, ".$DB->quote( T_( 'Poll the antispam blacklist' ) ).", ".$DB->quote( $poll_antispam_ctrl ).", ".$ctsk_params." )";
	$insert_values = array(
		$prune_pagecache_ctrl => $prune_pagecache,
		$prune_sessions_ctrl => $prune_sessions,
		$poll_antispam_ctrl => $poll_antispam );

	if( $is_upgrade )
	{ // Check if these jobs already exist, and don't create another
		$sql = 'SELECT count(ctsk_ID) as job_number, ctsk_controller
					FROM T_cron__task LEFT JOIN T_cron__log ON ctsk_ID = clog_ctsk_ID
						WHERE clog_status IS NULL AND
							( ctsk_controller = '.$DB->quote( $prune_pagecache_ctrl ).' OR
							ctsk_controller = '.$DB->quote( $prune_sessions_ctrl ).' OR
							ctsk_controller = '.$DB->quote( $poll_antispam_ctrl ).' )
						GROUP BY ctsk_controller';
		$result = $DB->get_results( $sql );
		foreach( $result as $row )
		{ // clear existing jobs insert values
			unset( $insert_values[$row->ctsk_controller] );
		}
	}

	$values = implode( ', ', $insert_values );
	if( empty( $values ) )
	{ // nothing to create
		return;
	}

	task_begin( T_( 'Creating default scheduled jobs... ' ) );
	$DB->query( '
		INSERT INTO T_cron__task ( ctsk_start_datetime, ctsk_repeat_after, ctsk_name, ctsk_controller, ctsk_params )
		VALUES '.$values, T_( 'Create default scheduled jobs' ) );
	task_end();
}

/**
 * Create a new blog
 * This funtion has to handle all needed DB dependencies!
 *
 * @todo move this to Blog object (only half done here)
 */
function create_blog(
	$blog_name,
	$blog_shortname,
	$blog_urlname,
	$blog_tagline = '',
	$blog_longdesc = '',
	$blog_skin_ID = 1,
	$kind = 'std' ) // standard blog; notorious variation: "photo"
{
	global $default_locale;

	$Blog = new Blog( NULL );

	$Blog->init_by_kind( $kind, $blog_name, $blog_shortname, $blog_urlname );

	$Blog->set( 'tagline', $blog_tagline );
	$Blog->set( 'longdesc', $blog_longdesc );
	$Blog->set( 'locale', $default_locale );
	$Blog->set( 'skin_ID', $blog_skin_ID );

	$Blog->dbinsert();

	$Blog->set( 'access_type', 'relative' );
	$Blog->set( 'siteurl', 'blog'.$Blog->ID.'.php' );

	$Blog->dbupdate();

	return $Blog->ID;
}


/**
 * This is called only for fresh installs and fills the tables with
 * demo/tutorial things.
 */
function create_demo_contents()
{
	global $baseurl, $new_db_version;
	global $random_password, $query;
	global $timestamp, $admin_email;
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;
	global $DB;
	global $default_locale;
	global $Plugins;

	/**
	 * @var FileRootCache
	 */
	global $FileRootCache;

	load_class( 'collections/model/_blog.class.php', 'Blog' );
	load_class( 'files/model/_file.class.php', 'File' );
	load_class( 'files/model/_filetype.class.php', 'FileType' );
	load_class( 'items/model/_link.class.php', 'Link' );

	task_begin('Assigning avatar to Admin... ');
	$edit_File = new File( 'user', 1, 'faceyourmanga_admin_boy.png' );
	// Load meta data AND MAKE SURE IT IS CREATED IN DB:
	$edit_File->load_meta( true );
	$UserCache = & get_UserCache();
	$User_Admin = & $UserCache->get_by_ID( 1 );
	$User_Admin->set( 'avatar_file_ID', $edit_File->ID );
	$User_Admin->dbupdate();
	task_end();

	task_begin('Creating demo blogger user... ');
	$User_Blogger = new User();
	$User_Blogger->set( 'login', 'ablogger' );
	$User_Blogger->set( 'pass', md5($random_password) ); // random
	$User_Blogger->set( 'nickname', 'Blogger A' );
	$User_Blogger->set_email( $admin_email );
	$User_Blogger->set( 'validated', 1 ); // assume it's validated
	$User_Blogger->set( 'ip', '127.0.0.1' );
	$User_Blogger->set( 'domain', 'localhost' );
	$User_Blogger->set( 'level', 1 );
	$User_Blogger->set( 'locale', $default_locale );
	$User_Blogger->set_datecreated( $timestamp++ );
	$User_Blogger->set_Group( $Group_Bloggers );
	$User_Blogger->dbinsert();
	task_end();

	task_begin('Creating demo user... ');
	$User_Demo = new User();
	$User_Demo->set( 'login', 'demouser' );
	$User_Demo->set( 'pass', md5($random_password) ); // random
	$User_Demo->set( 'nickname', 'Mr. Demo' );
	$User_Demo->set_email( $admin_email );
	$User_Demo->set( 'validated', 1 ); // assume it's validated
	$User_Demo->set( 'ip', '127.0.0.1' );
	$User_Demo->set( 'domain', 'localhost' );
	$User_Demo->set( 'level', 0 );
	$User_Demo->set( 'locale', $default_locale );
	$User_Demo->set( 'allow_msgform', 2 ); // set user msgform settings to allow only emails ( 2 = only emails )
	$User_Demo->set_datecreated( $timestamp++ );
	$User_Demo->set_Group( $Group_Users );
	$User_Demo->dbinsert();
	task_end();

	global $default_locale, $query, $timestamp;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;

	$default_blog_longdesc = T_("This is the long description for the blog named '%s'. %s");

	echo "Creating default blogs... ";

	$blog_shortname = 'Blog A';
	$blog_a_long = sprintf( T_('%s Title'), $blog_shortname );
	$blog_stub = 'a';
	$blog_a_ID = create_blog(
		$blog_a_long,
		$blog_shortname,
		$blog_stub,
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, '' ),
		1, 'std' ); // Skin ID

	$blog_shortname = 'Blog B';
	$blog_stub = 'b';
	$blog_b_ID = create_blog(
		sprintf( T_('%s Title'), $blog_shortname ),
		$blog_shortname,
		$blog_stub,
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, '' ),
		2, 'std' ); // Skin ID

	$blog_shortname = 'Linkblog';
	$blog_stub = 'links';
	$blog_more_longdesc = '<br />
<br />
<strong>'.T_("The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.").'</strong>';
	$blog_linkblog_ID = create_blog(
		'Linkblog',
		$blog_shortname,
		$blog_stub,
		T_('Some interesting links...'),
		sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
		3, 'std' ); // SKin ID

	$blog_shortname = 'Photoblog';
	$blog_stub = 'photos';
	$blog_more_longdesc = '<br />
<br />
<strong>'.T_("This is a photoblog, optimized for displaying photos.").'</strong>';
	$blog_photoblog_ID = create_blog(
		'Photoblog',
		$blog_shortname,
		$blog_stub,
		T_('This blog shows photos...'),
		sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
		4, 'photo' ); // SKin ID

	echo "OK.<br />\n";


	global $query, $timestamp;

	echo 'Creating sample categories... ';

	// Create categories for blog A
	$cat_ann_a = cat_create( T_('Welcome'), 'NULL', $blog_a_ID );
	$cat_news = cat_create( T_('News'), 'NULL', $blog_a_ID );
	$cat_bg = cat_create( T_('Background'), 'NULL', $blog_a_ID );
	$cat_fun = cat_create( T_('Fun'), 'NULL', $blog_a_ID );
	$cat_life = cat_create( T_('In real life'), $cat_fun, $blog_a_ID );
	$cat_web = cat_create( T_('On the web'), $cat_fun, $blog_a_ID );
	$cat_sports = cat_create( T_('Sports'), $cat_life, $blog_a_ID );
	$cat_movies = cat_create( T_('Movies'), $cat_life, $blog_a_ID );
	$cat_music = cat_create( T_('Music'), $cat_life, $blog_a_ID );

	// Create categories for blog B
	$cat_ann_b = cat_create( T_('Announcements'), 'NULL', $blog_b_ID );
	$cat_b2evo = cat_create( T_('b2evolution Tips'), 'NULL', $blog_b_ID );
	$cat_additional_skins = cat_create( T_('Get additional skins'), 'NULL', $blog_b_ID );

	// Create categories for linkblog
	$cat_linkblog_b2evo = cat_create( 'b2evolution', 'NULL', $blog_linkblog_ID );
	$cat_linkblog_contrib = cat_create( T_('Contributors'), 'NULL', $blog_linkblog_ID );

	// Create categories for photoblog
	$cat_photo_album = cat_create( T_('Monument Valley'), 'NULL', $blog_photoblog_ID );

	echo "OK.<br />\n";


	echo 'Creating sample posts... ';

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('First Post'), T_('<p>This is the first post.</p>

<p>It appears in a single category.</p>'), $now, $cat_ann_a );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Second post'), T_('<p>This is the second post.</p>

<p>It appears in multiple categories.</p>'), $now, $cat_news, array( $cat_ann_a ) );

	// Insert sidebar links into Blog B
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Skin faktory'), '', $now, $cat_additional_skins, array(), 'published', 'en-US', '', 'http://www.skinfaktory.com/', 'open', array('default'), 3000 );

	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('b2evo skins repository'), '', $now, $cat_additional_skins, array(), 'published', 'en-US', '', 'http://skins.b2evolution.net/', 'open', array('default'), 3000 );

	// PHOTOBLOG:
	/**
	 * @var FileRootCache
	 */
	// $FileRootCache = & get_FileRootCache();
	// $FileRoot = & $FileRootCache->get_by_type_and_ID( 'collection', $blog_photoblog_ID, true );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Bus Stop Ahead'), 'In the middle of nowhere: a school bus stop where you wouldn\'t really expect it!',
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = new File( 'shared', 0, 'monument-valley/bus-stop-ahead.jpg' );
	$edit_File->link_to_Item( $edited_Item );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('John Ford Point'), 'Does this scene look familiar? You\'ve probably seen it in a couple of John Ford westerns!',
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = new File( 'shared', 0, 'monument-valley/john-ford-point.jpg' );
	$edit_File->link_to_Item( $edited_Item );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Monuments'), 'This is one of the most famous views in Monument Valley. I like to frame it with the dirt road in order to give a better idea of the size of those things!',
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
	$edit_File->link_to_Item( $edited_Item );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Road to Monument Valley'), 'This gives a pretty good idea of the Monuments you\'re about to drive into...',
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley-road.jpg' );
	$edit_File->link_to_Item( $edited_Item );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Monument Valley'), T_('This is a short photo album demo. Use the arrows to navigate between photos. Click on "Index" to see a thumbnail index.'),
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley.jpg' );
	$edit_File->link_to_Item( $edited_Item );


	// POPULATE THE LINKBLOG:

	// Insert a post into linkblog:
	// walter : a weird line of code to create a post in the linkblog a minute after the others.
    // It will show a bug on linkblog agregation by category
	$timestamp++;
	$now = date('Y-m-d H:i:s',$timestamp + 59);
	$edited_Item = new Item();
	$edited_Item->insert( 1, 'Evo Factory', '', $now, $cat_linkblog_contrib, array(), 'published', 'en-US', '', 'http://evofactory.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, 'Alex', '', $now, $cat_linkblog_contrib, array(), 'published', 'ru-RU', '', 'http://b2evo.sonorth.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, 'Francois', '', $now, $cat_linkblog_contrib, array(), 'published', 'fr-FR', '', 'http://fplanque.com/', 'disabled', array() );


	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, 'Blog news', '', $now, $cat_linkblog_b2evo, array(), 'published', 'en-US', '', 'http://b2evolution.net/news.php', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, 'Web hosting', '', $now, $cat_linkblog_b2evo, array(), 'published', 'en-US', '', 'http://b2evolution.net/web-hosting/blog/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, 'Manual', '', $now, $cat_linkblog_b2evo, array(), 'published',	'en-US', '', 'http://manual.b2evolution.net/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, 'Support', '', $now, $cat_linkblog_b2evo, array(), 'published', 'en-US', '', 'http://forums.b2evolution.net/', 'disabled', array() );



 	$info_page = T_("<p>This blog is powered by b2evolution.</p>

<p>You are currently looking at an info page about %s.</p>

<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.</p>

<p>If needed, an evoskin can format info pages differently from regular posts.</p>");

	// Insert a PAGE:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("About Blog B"), sprintf( $info_page, T_('Blog B') ), $now, $cat_ann_b,
		array(), 'published', '#', '', '', 'open', array('default'), 1000 );

	// Insert a PAGE:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("About Blog A"), sprintf( $info_page, T_('Blog A') ), $now, $cat_ann_a,
		array(), 'published', '#', '', '', 'open', array('default'), 1000 );

	// Insert a PAGE:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("About this system"), T_("<p>This blog platform is powered by b2evolution.</p>

<p>You are currently looking at an info page about this system. It is cross-posted among the demo blogs. Thus, this page will be linked on each of these blogs.</p>

<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.</p>

<p>If needed, an evoskin can format info pages differently from regular posts.</p>"), $now, $cat_ann_a,
		array( $cat_ann_a, $cat_ann_b, $cat_linkblog_b2evo ), 'published', '#', '', '', 'open', array('default'), 1000 );
	$edit_File = new File( 'shared', 0, 'logos/b2evolution8.png' );
	$edit_File->link_to_Item( $edited_Item );


	/*
	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("Default Intro post"), T_("This uses post type \"Intro-All\"."),
												$now, $cat_b2evo, array( $cat_ann_b ), 'published', '#', '', '', 'open', array('default'), 1600 );
	*/

	// Insert a post:
	$now = date('Y-m-d H:i:s', ($timestamp++ - 31536000) ); // A year ago
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("Main Intro post"), T_("This is the main intro post. It appears on the homepage only."),
												$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 1500 );

	// Insert a post:
	$now = date('Y-m-d H:i:s', ($timestamp++ - 31536000) ); // A year ago
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("b2evolution tips category &ndash; Sub Intro post"), T_("This uses post type \"Intro-Cat\" and is attached to the desired Category(ies)."),
												$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 1520 );

	// Insert a post:
	$now = date('Y-m-d H:i:s', ($timestamp++ - 31536000) ); // A year ago
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("Widgets tag &ndash; Sub Intro post"), T_("This uses post type \"Intro-Tag\" and is tagged with the desired Tag(s)."),
												$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 1530 );
	$edited_Item->set_tags_from_string( 'widgets' );
	//$edited_Item->dbsave();
	$edited_Item->insert_update_tags( 'update' );

	// Insert a post:
	// TODO: move to Blog A
	$now = date('Y-m-d H:i:s', $timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("Featured post"), T_("<p>This is a demo of a featured post.</p>

<p>It will be featured whenever we have no specific \"Intro\" post to display for the current request. To see it in action, try displaying the \"Announcements\" category.</p>

<p>Also note that when the post is featured, it does not appear in the regular post flow.</p>"),
	$now, $cat_b2evo, array( $cat_ann_b ) );
	$edited_Item->set( 'featured', 1 );
	$edited_Item->dbsave();

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("Apache optimization..."), sprintf( T_("<p>b2evolution comes with an <code>.htaccess</code> file destined to optimize the way b2evolution is handled by your webseerver (if you are using Apache). In some circumstances, that file may not be automatically activated at setup. Please se the man page about <a %s>Tricky Stuff</a> for more information.</p>

<p>For further optimization, please review the manual page about <a %s>Performance optimization</a>. Depending on your current configuration and on what your <a %s>web hosting</a> company allows you to do, you may increase the speed of b2evolution by up to a factor of 10!</p>"),
'href="http://manual.b2evolution.net/Tricky_stuff"',
'href="http://manual.b2evolution.net/Performance_optimization"',
'href="http://b2evolution.net/web-hosting/"' ),
												$now, $cat_b2evo, array( $cat_ann_b ) );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("Skins, Stubs, Templates &amp; website integration..."), T_("<p>By default, blogs are displayed using an evoskin. (More on skins in another post.)</p>

<p>This means, blogs are accessed through '<code>index.php</code>', which loads default parameters from the database and then passes on the display job to a skin.</p>

<p>Alternatively, if you don't want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided '<code>a_stub.php</code>' and call your blog through this stub instead of index.php .</p>

<p>Finally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided '<code>a_noskin.php</code>'.</p>

<p>If you want to integrate a b2evolution blog into a complex website, you'll probably want to do it by copy/pasting code from <code>a_noskin.php</code> into a page of your website.</p>

<p>You will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.</p>

<p>Either way, make sure you go to the blogs admin and set the correct access method/URL for your blog. Otherwise, the permalinks will not function properly.</p>"), $now, $cat_b2evo );
	$edited_Item->set_tags_from_string( 'skins' );
	//$edited_Item->dbsave();
	$edited_Item->insert_update_tags( 'update' );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("About widgets..."), T_('<p>b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.</p>

<p>You can add, remove and reorder widgets from the Blog Settings tab in the admin interface.</p>

<p>Note: in order to be displayed, widgets are placed in containers. Each container appears in a specific place in an evoskin. If you change your blog skin, the new skin may not use the same containers as the previous one. Make sure you place your widgets in containers that exist in the specific skin you are using.</p>'), $now, $cat_b2evo );
	$edited_Item->set_tags_from_string( 'widgets' );
	//$edited_Item->dbsave();
	$edited_Item->insert_update_tags( 'update' );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("About skins..."), T_('<p>By default, b2evolution blogs are displayed using an evoskin.</p>

<p>You can change the skin used by any blog by editing the blog settings in the admin interface.</p>

<p>You can download additional skins from the <a href="http://skins.b2evolution.net/" target="_blank">skin site</a>. To install them, unzip them in the /blogs/skins directory, then go to General Settings &gt; Skins in the admin interface and click on "Install new".</p>

<p>You can also create your own skins by duplicating, renaming and customizing any existing skin folder from the /blogs/skins directory.</p>

<p>To start customizing a skin, open its "<code>index.main.php</code>" file in an editor and read the comments in there. Note: you can also edit skins in the "Files" tab of the admin interface.</p>

<p>And, of course, read the <a href="http://manual.b2evolution.net/Skins_2.0" target="_blank">manual on skins</a>!</p>'), $now, $cat_b2evo );
	$edited_Item->set_tags_from_string( 'skins' );
	$edited_Item->set( 'featured', 1 );
	$edited_Item->dbsave();
	// $edited_Item->insert_update_tags( 'update' );


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Image post'), T_('<p>This post has an image attached to it. The image is automatically resized to fit the current blog skin. You can zoom in by clicking on the thumbnail.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'), $now, $cat_bg );
	$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
	$edit_File->link_to_Item( $edited_Item );


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('This is a multipage post'), T_('<p>This is page 1 of a multipage post.</p>

<p>You can see the other pages by clicking on the links below the text.</p>

<!--nextpage-->

<p>This is page 2.</p>

<!--nextpage-->

<p>This is page 3.</p>

<!--nextpage-->

<p>This is page 4.</p>

<p>It is the last page.</p>'), $now, $cat_bg );


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Extended post with no teaser'), T_('<p>This is an extended post with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.</p>

<!--more--><!--noteaser-->

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_bg );


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_('Extended post'), T_('<p>This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.</p>

<!--more-->

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_bg );


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = new Item();
	$edited_Item->insert( 1, T_("Welcome to b2evolution!"), T_("<p>Four blogs have been created with sample contents:</p>

<ul>
	<li><strong>Blog A</strong>: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.</li>
	<li><strong>Blog B</strong>: You can access it from a link at the top of the page. It contains information about more advanced features.</li>
	<li><strong>Linkblog</strong>: By default, the linkblog is included as a \"Blogroll\" in the sidebar of both Blog A &amp; Blog B.</li>
	<li><strong>Photoblog</strong>: This blog is an example of how you can use b2evolution to showcase photos, with one photo per page as well as a thumbnail index.</li>
</ul>

<p>You can add new blogs, delete unwanted blogs and customize existing blogs (title, sidebar, blog skin, widgets, etc.) from the Blog Settings tab in the admin interface.</p>"), $now, $cat_ann_a );
	$edit_File = new File( 'shared', 0, 'logos/b2evolution8.png' );
	$edit_File->link_to_Item( $edited_Item );




	echo "OK.<br />\n";



	echo 'Creating sample comments... ';

	$now = date('Y-m-d H:i:s');
	$query = "INSERT INTO T_comments( comment_post_ID, comment_type, comment_author,
																				comment_author_email, comment_author_url, comment_author_IP,
																				comment_date, comment_content, comment_karma)
						VALUES( 1, 'comment', 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1',
									 '$now', '".
									 $DB->escape(T_('Hi, this is a comment.<br />To delete a comment, just log in, and view the posts\' comments, there you will have the option to edit or delete them.')). "', 0)";
	$DB->query( $query );

	echo "OK.<br />\n";


	echo 'Creating default group/blog permissions... ';
	// Admin for blog A:
	$query = "
		INSERT INTO T_coll_group_perms( bloggroup_blog_ID, bloggroup_group_ID, bloggroup_ismember,
			bloggroup_perm_poststatuses, bloggroup_perm_delpost, bloggroup_perm_edit_ts,
			bloggroup_perm_draft_cmts, bloggroup_perm_publ_cmts, bloggroup_perm_depr_cmts,
			bloggroup_perm_cats, bloggroup_perm_properties,
			bloggroup_perm_media_upload, bloggroup_perm_media_browse, bloggroup_perm_media_change )
		VALUES
			( $blog_a_ID, ".$Group_Admins->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1 ),
			( $blog_a_ID, ".$Group_Privileged->ID.", 1, 'published,deprecated,protected,private,draft', 1, 0, 1, 1, 1, 0, 0, 1, 1, 1 ),
			( $blog_a_ID, ".$Group_Bloggers->ID.", 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 0, 0, 0, 1, 1, 0 ),
			( $blog_b_ID, ".$Group_Admins->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1 ),
			( $blog_b_ID, ".$Group_Privileged->ID.", 1, 'published,deprecated,protected,private,draft', 1, 0, 1, 1, 1, 0, 0, 1, 1, 1 ),
			( $blog_b_ID, ".$Group_Bloggers->ID.", 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 0, 0, 0, 1, 1, 0 ),
			( $blog_linkblog_ID, ".$Group_Admins->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1 ),
			( $blog_linkblog_ID, ".$Group_Privileged->ID.", 1, 'published,deprecated,protected,private,draft', 1, 0, 1, 1, 1, 0, 0, 1, 1, 1 ),
			( $blog_linkblog_ID, ".$Group_Bloggers->ID.", 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 0, 0, 0, 1, 1, 0 )";
	$DB->query( $query );
	echo "OK.<br />\n";

	/*
	// Note: we don't really need this any longer, but we might use it for a better default setup later...
	echo 'Creating default user/blog permissions... ';
	// Admin for blog A:
	$query = "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
							bloguser_perm_cats, bloguser_perm_properties,
							bloguser_perm_media_upload, bloguser_perm_media_browse, bloguser_perm_media_change )
						VALUES
							( $blog_a_ID, ".$User_Demo->ID.", 1,
							'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1 )";
	$DB->query( $query );
	echo "OK.<br />\n";
	*/


	install_basic_widgets();

	load_funcs( 'tools/model/_system.funcs.php' );
	if( !system_init_caches() )
	{
		echo "<strong>".T_('The /cache folder could not be created/written to. b2evolution will still work but without caching, which will make it operate slower than optimal.')."</strong><br />\n";
	}
}


/*
 * $Log: _functions_create.php,v $
 */
?>