<?php
// Real WordPress/MariaDB, connection-scoped TEMPORARY tables only.
// No activation, no production options, posts, users or statistics are written.
ob_start();
if (!defined('DOING_AJAX')) define('DOING_AJAX',true);
$_SERVER['HTTPS']='on';
if (!defined('ABSPATH')) require_once '/var/www/html/wp-load.php';
global $wpdb,$checks,$totals,$guard;
$root=$args[0]??$argv[1]??dirname(__DIR__).'/plugins/seitenblick';
$GLOBALS['sbkt_settings']=['enabled'=>true,'browser'=>true,'retention'=>90,'delete_on_uninstall'=>false];
add_filter('pre_option_sbk_settings',fn()=> $GLOBALS['sbkt_settings']);
add_filter('pre_option_sbk_started',fn()=>gmdate('c',time()-150*DAY_IN_SECONDS));
require $root.'/seitenblick.php';
$prefix=$wpdb->prefix;
$wpdb->prefix='sbkt_'.bin2hex(random_bytes(6)).'_';
foreach (sbk_schema_sql() as $sql) {
    if ($wpdb->query(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$sql))===false) { throw new RuntimeException('Temporäre Testtabellen nicht verfügbar.'); }
}
[$totals,$guard]=sbk_tables();
$checks=0;
function sbkt_check($ok,$label) { if(!$ok)throw new RuntimeException($label);$GLOBALS['checks']++;echo "PASS $label\n"; }
function sbkt_request($body,$origin=null) {
    $r=new WP_REST_Request('POST','/seitenblick/v1/open');
    $r->set_header('origin',$origin??home_url('/'));
    $r->set_header('content-type','application/json');
    $r->set_header('sec-fetch-site','same-origin');
    $r->set_body(wp_json_encode($body));
    return $r;
}
function sbkt_sum($column) {global $wpdb,$totals;return (int)$wpdb->get_var("SELECT SUM($column) FROM $totals");}
try {
    wp_set_current_user(0);$_COOKIE=[];
    $_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/150.0.0.0 Safari/537.36';
    unset($_SERVER['HTTP_DNT'],$_SERVER['HTTP_SEC_GPC']);
    sbkt_check(sbk_origin('https://EXAMPLE.org/')==='https://example.org:443','Origins werden kanonisch verglichen');
    foreach(['javascript:alert(1)','https://user:pass@example.org/','https://example.org:444','https://wald-schein.de.evil.example/'] as $bad) {
        sbkt_check(sbk_origin($bad)!==sbk_origin(home_url('/')),'Fremde oder manipulierte Origin abgewiesen');
    }
    foreach(['/wp-admin/?secret=1','//evil.example','/../private',"/x\nheader",'/x?name=Rene','/x\\secret'] as $path)sbkt_check(!sbk_valid_path($path),'Private/ungültige Seitenkennung abgewiesen');
    sbkt_check(isset(sbk_routes()['/'])&&isset(sbk_routes()['/sample-page/']),'Öffentliche WordPress-Seiten registriert');
    foreach(['Blackbox Exporter/0.25.0','curl/8.0','Python-urllib/3.10','Googlebot','HeadlessChrome'] as $agent) {
        $_SERVER['HTTP_USER_AGENT']=$agent;sbkt_check(sbk_excluded(),'Betriebsprüfung/Bot ausgeschlossen');
    }
    $_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 Chrome/150 Safari/537.36';
    $_SERVER['HTTP_DNT']='1';sbkt_check(sbk_excluded(),'Do Not Track ausgeschlossen');unset($_SERVER['HTTP_DNT']);
    $_SERVER['HTTP_SEC_GPC']='1';sbkt_check(sbk_excluded(),'Global Privacy Control ausgeschlossen');unset($_SERVER['HTTP_SEC_GPC']);
    $_COOKIE['wordpress_logged_in_test']='test';sbkt_check(sbk_excluded(),'Anmeldesitzung auch ohne REST-Nonce ausgeschlossen');$_COOKIE=[];
    for($i=0;$i<3;$i++)sbkt_check(sbk_limit('test-limit',3),'Globales Limit lässt erlaubte Menge zu');
    sbkt_check(!sbk_limit('test-limit',3),'Globales Limit blockiert Mehrmenge');
    sbkt_check(!sbk_add('/',["evil_column"=>1]),'Unbekannte Statistikspalte abgewiesen');
    sbkt_check(!sbk_add('/',["server_views"=>-1]),'Negative Zähler abgewiesen');
    sbkt_check(sbk_add('/',["server_views"=>1,"desktop"=>1,"direct"=>1]),'Atomare Datenbank-Summenzählung');
    sbkt_check(sbk_add('/',["server_views"=>1]),'Vorhandene Summe atomar erhöht');
    sbkt_check(sbkt_sum('server_views')===2,'Serveraufrufe korrekt summiert');
    $flags=['statistics'=>true,'external'=>false,'marketing'=>false];
    $cookie=sbk_encode_consent($flags,true);
    sbkt_check(sbk_decode_consent($cookie)['choices']===$flags,'Signierte Auswahl gelesen');
    sbkt_check(sbk_encode_consent($flags,true)===sbk_encode_consent($flags,true),'Gleiche Auswahl am selben Tag ohne individuelle Kennung');
    sbkt_check(sbk_decode_consent(substr($cookie,0,-1).(str_ends_with($cookie,'a')?'b':'a'))===null,'Cookie-Manipulation erkannt');
    sbkt_check(sbk_decode_consent($cookie,time()+91*DAY_IN_SECONDS)===null,'Abgelaufene Auswahl abgewiesen');
    sbkt_check(sbk_decode_consent(sbk_encode_consent($flags,false))===null,'Optionale Freigabe ohne Alters-/Sorgebestätigung ungültig');
    $_COOKIE[sbk_cookie_name()]=$cookie;
    $r=sbkt_request(['path'=>'/','consent'=>'v1','adult'=>true]);
    sbkt_check(sbk_permission($r)===true,'Same-Origin und gültige Einwilligung erlaubt');
    $legacy=new WP_REST_Request('POST','/seitenblick/v1/consent');
    $legacy->set_header('origin',home_url('/'));
    $legacy->set_header('content-type','application/json');
    $legacy->set_body(wp_json_encode(['action'=>'read']));
    sbkt_check(sbk_consent_permission($legacy)===true,'Gültige Herkunft ohne optionalen Fetch-Metadata-Header erlaubt');
    $missingOrigin=new WP_REST_Request('POST','/seitenblick/v1/consent');
    $missingOrigin->set_header('content-type','application/json');
    $missingOrigin->set_body(wp_json_encode(['action'=>'read']));
    sbkt_check(is_wp_error(sbk_consent_permission($missingOrigin)),'Fehlende Herkunft auch ohne Fetch-Metadata abgewiesen');

    sbkt_check(is_wp_error(sbk_permission(sbkt_request(['path'=>'/'],'https://evil.example'))),'Fremd-Origin vor Verarbeitung blockiert');
    $other=sbkt_request(['path'=>'/']);$other->set_header('sec-fetch-site','cross-site');
    sbkt_check(is_wp_error(sbk_permission($other)),'Cross-Site-Fetch blockiert');
    $large=sbkt_request(['path'=>'/']);$large->set_body(str_repeat('x',1100));
    sbkt_check(is_wp_error(sbk_permission($large)),'Überlange Anfrage vor Verarbeitung blockiert');
    unset($_COOKIE[sbk_cookie_name()]);
    sbkt_check(is_wp_error(sbk_permission($r)),'Ohne signiertes Einwilligungs-Cookie keine Browsermessung');
    $_COOKIE[sbk_cookie_name()]=$cookie;
    $invalid=sbk_open(sbkt_request(['path'=>'/#/art/unbekannt','consent'=>'v1','adult'=>true]));
    sbkt_check(is_wp_error($invalid),'Frei erfundene App-Seite nicht gezählt');
    sbkt_check(is_wp_error(sbk_open(sbkt_request(['path'=>'/','consent'=>'v1','adult'=>true,'name'=>'Rene']))),'Zusätzliche personenbezogene Felder abgewiesen');
    $opened=sbk_open($r);sbkt_check($opened instanceof WP_REST_Response,'Seitenansicht eröffnet');
    $token=$opened->get_data()['token'];
    sbkt_check(strlen($token)===64 && sbkt_sum('browser_views')===1,'Eine freiwillige Ansicht gezählt');
    $key=hash('sha256',$token);
    sbkt_check($wpdb->get_var($wpdb->prepare("SELECT token_hash FROM $guard WHERE kind='view' AND token_hash=%s",$token))===null,'Nur Hash des zufälligen Ansichtsbelegs gespeichert');
    $wpdb->update($guard,['started'=>time()-90],['token_hash'=>$key]);
    $heart=sbkt_request(['token'=>$token,'seconds'=>20,'sequence'=>1]);
    sbkt_check(sbk_heartbeat($heart) instanceof WP_REST_Response,'Plausible aktive Sekunden addiert');
    sbkt_check(sbkt_sum('active_seconds')===20 && sbkt_sum('timed_views')===1,'Sekunden und Zeitstichprobe korrekt');
    sbkt_check(is_wp_error(sbk_heartbeat($heart)),'Wiederholter Beleg atomar abgewiesen');
    sbkt_check(sbkt_sum('active_seconds')===20,'Replay erhöht die Summe nicht');
    sbkt_check(sbk_heartbeat(sbkt_request(['token'=>$token,'seconds'=>40,'sequence'=>2])) instanceof WP_REST_Response,'Kumulative Zeit fortgeschrieben');
    sbkt_check(sbkt_sum('engaged_views')===1,'30-Sekunden-Schwelle genau einmal gezählt');
    foreach([
        ['token'=>$token,'seconds'=>39,'sequence'=>3],
        ['token'=>$token,'seconds'=>101,'sequence'=>3],
        ['token'=>$token,'seconds'=>1000,'sequence'=>3],
        ['token'=>$token,'seconds'=>40.5,'sequence'=>3],
        ['token'=>$token,'seconds'=>40,'sequence'=>2],
        ['token'=>$token,'seconds'=>40,'sequence'=>3,'ip'=>'127.0.0.1'],
        ['token'=>"x' OR 1=1 --",'seconds'=>40,'sequence'=>3],
    ] as $body)sbkt_check(is_wp_error(sbk_heartbeat(sbkt_request($body))),'Manipulierte Zeit/Replay/Fremdfeld/SQL-Eingabe abgewiesen');
    sbkt_check(sbkt_sum('active_seconds')===40 && sbkt_sum('timed_views')===1 && sbkt_sum('engaged_views')===1,'Fehler hinterlassen keine Teilsummen');
    $wpdb->update($guard,['expires'=>time()-1],['token_hash'=>$key]);
    sbkt_check(is_wp_error(sbk_heartbeat(sbkt_request(['token'=>$token,'seconds'=>41,'sequence'=>3]))),'Abgelaufener Ansichtsbeleg abgewiesen');
    sbk_cleanup();
    sbkt_check($wpdb->get_var($wpdb->prepare("SELECT token_hash FROM $guard WHERE token_hash=%s",$key))===null,'Abgelaufener Beleg entfernt');
    $opened=sbk_open($r);$token=$opened->get_data()['token'];
    $_COOKIE[sbk_cookie_name()]=sbk_encode_consent(['statistics'=>false,'external'=>false,'marketing'=>false],false);
    sbkt_check(is_wp_error(sbk_permission($r)),'Widerruf sperrt weitere Browsermessungen serverseitig');
    sbkt_check(sbk_consent_permission(sbkt_request(['token'=>$token]))===true,'Widerrufs-Löschung bleibt auch nach Rücknahme erreichbar');
    sbkt_check(sbk_revoke(sbkt_request(['token'=>$token])) instanceof WP_REST_Response,'Widerruf löscht Ansichtsbeleg');
    sbkt_check($wpdb->get_var($wpdb->prepare("SELECT token_hash FROM $guard WHERE token_hash=%s",hash('sha256',$token)))===null,'Widerrufener Beleg gelöscht');
    $badConsent=sbkt_request(['action'=>'save','choices'=>$flags,'adult'=>false,'policy'=>sbk_policy()]);
    sbkt_check(is_wp_error(sbk_consent($badConsent)),'Freiwillige Freigabe ohne Bestätigung nicht gespeichert');
    $badConsent=sbkt_request(['action'=>'save','choices'=>$flags,'adult'=>true,'policy'=>'old']);
    sbkt_check(is_wp_error(sbk_consent($badConsent)),'Veraltete Richtlinie nicht gespeichert');
    $read=sbk_consent(sbkt_request(['action'=>'read']));
    sbkt_check($read->get_headers()['Cache-Control']==='private, no-store, max-age=0','Einwilligungsauskunft wird nicht gecacht');
    $GLOBALS['sbkt_settings']['browser']=false;
    sbkt_check(sbk_decode_consent($cookie)===null,'Geänderte Messrichtlinie verlangt neue Auswahl');
    $GLOBALS['sbkt_settings']['browser']=true;
    $title='<img src=x onerror=alert(1)>';
    $tag=apply_filters('script_loader_tag','<script src="https://evil.example/a.js"></script>','external','https://evil.example/a.js');
    sbkt_check(!str_contains($tag,' src=') && str_contains($tag,'application/x-seitenblick'),'Nicht zugeordnete externe WordPress-Skripte vor Freigabe inert');
    $style=apply_filters('style_loader_tag','<link href="https://evil.example/a.css" rel="stylesheet">','external','https://evil.example/a.css','all');
    sbkt_check(!str_contains($style,' href=') && str_contains($style,'<template'),'Externes Stylesheet ohne Netzwerkadresse im aktiven Tag');
    $local='<script src="'.home_url('/local.js').'"></script>';
    sbkt_check(apply_filters('script_loader_tag',$local,'local',home_url('/local.js'))===$local,'Lokales nicht registriertes Skript unverändert');
    $GLOBALS['sbkt_settings']['enabled_services']=['youtube'];
    $video=do_shortcode('[seitenblick_video anbieter="youtube" id="abcdefghijk" titel="Video"]');
    sbkt_check(!str_contains($video,'<iframe') && str_contains($video,'data-sbk-src='),'Video vor Zustimmung ohne iframe/Netzwerkabruf');
    sbkt_check(do_shortcode('[seitenblick_video anbieter="youtube" id="../evil"]')==='','Manipulierte Video-ID abgewiesen');
    $GLOBALS['sbkt_settings']['enabled_services']=[];
    foreach(['=HYPERLINK("evil")',' +cmd','@SUM(1)','-1+2',"\t=1+1"] as $cell)sbkt_check(str_starts_with(sbk_csv_cell($cell),"'"),'CSV-Formeleinschleusung entschärft');
    sbkt_check(sbk_csv_cell('Amsel')==='Amsel','Normale CSV-Zelle unverändert');
    foreach(['7 OR 1=1',[],1,999] as $days)sbkt_check(sbk_days($days)===null,'Unzulässiger Statistikzeitraum abgewiesen');
    $adminRequest=new WP_REST_Request('GET','/seitenblick/v1/stats');
    sbkt_check(is_wp_error(sbk_admin_permission($adminRequest)),'Statistik für Gäste gesperrt');
    $admin=get_user_by('login','review');sbkt_check((bool)$admin,'Bestehendes Administratorkonto lesend gefunden');wp_set_current_user($admin->ID);
    sbkt_check(is_wp_error(sbk_admin_permission($adminRequest)),'Administratorkonto ohne CSRF-Nonce gesperrt');
    $adminRequest->set_header('x-wp-nonce',wp_create_nonce('wp_rest'));
    sbkt_check(sbk_admin_permission($adminRequest)===true,'Berechtigter Statistikabruf mit Nonce');
    wp_set_current_user(0);
    sbkt_check(is_wp_error(sbk_admin_permission($adminRequest)),'Nonce allein gewährt keine Berechtigung');
    $report=sbk_report(7);
    sbkt_check($report['current']['server_views']===2 && $report['current']['active_seconds']===40,'Bericht liest ausschließlich aggregierte Summen');
    $old=sbk_bucket(time()-100*DAY_IN_SECONDS);sbk_add('/',['server_views'=>9],$old);sbk_cleanup();
    sbkt_check(sbkt_sum('server_views')===2,'Aufbewahrungsfrist entfernt alte Summen');
    // Invoke only this plugin's server-count callback in an otherwise untouched WordPress.
    add_filter('wp_doing_ajax','__return_false');
    $_SERVER['REQUEST_METHOD']='GET';$_SERVER['REQUEST_URI']='/';
    $before=sbkt_sum('server_views');
    $_COOKIE[sbk_cookie_name()]=sbk_encode_consent(['statistics'=>false,'external'=>false,'marketing'=>false],false);
    foreach ($GLOBALS['wp_filter']['template_redirect']->callbacks[20] as $callback) {
        $function=$callback['function'];
        if ($function instanceof Closure && str_ends_with((new ReflectionFunction($function))->getFileName(),'/includes/collection.php')) {
            $function();
            sbkt_check(sbkt_sum('server_views')===$before,'Gespeicherte Ablehnung verhindert weitere Serverzählung');
            $_COOKIE=[];$function();
            sbkt_check(sbkt_sum('server_views')===$before+1,'Allgemeiner erlaubter Seitenaufruf wird gezählt');
        }
    }
    // New settings are parsed without writing persistent WordPress options.
    $base=['enabled'=>'1','browser'=>'1','retention'=>'90','texts'=>['title'=>'Meine Auswahl <script>alert(1)</script>'],
        'ip_blacklist'=>"192.0.2.20\n2001:db8::/32",'trusted_proxies'=>'192.0.2.2','services'=>[]];
    $parsed=sbk_parse_settings($base);
    sbkt_check(is_array($parsed)&&!str_contains($parsed['texts']['title'],'<script>'),'Bearbeitbare Texte ohne HTML/JavaScript');
    sbkt_check($parsed['ip_blacklist']===['192.0.2.20','2001:db8::/32'],'IPv4/IPv6-Ausschlüsse normalisiert');
    foreach (['hostname.example','1.2.3.999','10.0.0.1/33','2001:db8::/129','10.0.0.1/08','1.2.3.4,5.6.7.8',"\0"] as $bad) {
        sbkt_check(is_wp_error(sbk_parse_ip_list($bad)),'Ungültiger IP-/CIDR-Eintrag abgewiesen');
    }
    sbkt_check(sbk_parse_ip_list("192.0.2.7/24\n192.0.2.0/24")===['192.0.2.0/24'],'Netzadressen kanonisch und ohne Duplikat');
    sbkt_check(is_array(sbk_parse_ip_list(str_repeat("192.0.2.1\n",128))),'128 Einträge mit abschließender Leerzeile erlaubt');
    sbkt_check(is_wp_error(sbk_parse_ip_list(str_repeat("192.0.2.1\n",129))),'Mehr als 128 IP-Einträge abgewiesen');
    sbkt_check(sbk_ip_matches('192.0.2.255',['192.0.2.0/24'])&&!sbk_ip_matches('192.0.3.1',['192.0.2.0/24']),'IPv4-CIDR-Grenze korrekt');
    sbkt_check(sbk_ip_matches('2001:db8:ffff::1',['2001:db8::/32'])&&!sbk_ip_matches('2001:db9::1',['2001:db8::/32']),'IPv6-CIDR-Grenze korrekt');
    sbkt_check(sbk_ip_matches('::ffff:192.0.2.1',['192.0.2.1']),'IPv4-mapped IPv6 normalisiert');
    foreach ([['retention'=>'90abc'],['texts'=>['title'=>[]]],['texts'=>['unknown'=>'X']],['texts'=>['title'=>str_repeat('a',161)]],['services'=>['evil'=>['enabled'=>'1']]],['trusted_proxies'=>'0.0.0.0/0'],['trusted_proxies'=>'::/0'],['services'=>['youtube'=>['enabled'=>'1','privacy'=>'javascript:alert(1)']]],['services'=>['youtube'=>['enabled'=>'1','privacy'=>'https://user:pass@example.org/']]]] as $bad) {
        sbkt_check(is_wp_error(sbk_parse_settings(array_replace($base,$bad))),'Ungültige Einstellungen vor Speicherung abgewiesen');
    }
    $GLOBALS['sbkt_settings']['ip_blacklist']=['192.0.2.20'];$GLOBALS['sbkt_settings']['trusted_proxies']=[];
    $_SERVER['REMOTE_ADDR']='203.0.113.9';$_SERVER['HTTP_X_FORWARDED_FOR']='192.0.2.20';
    sbkt_check(sbk_client_ip()==='203.0.113.9'&&!sbk_ip_excluded(),'Unvertrauter Absender kann IP-Ausschluss nicht fälschen');
    $GLOBALS['sbkt_settings']['trusted_proxies']=['192.0.2.2'];$_SERVER['REMOTE_ADDR']='192.0.2.2';
    $_SERVER['HTTP_X_FORWARDED_FOR']='192.0.2.20, 203.0.113.9';
    sbkt_check(sbk_client_ip()==='203.0.113.9'&&!sbk_ip_excluded(),'Proxy-Kette prüft rechts nach links statt fremdem ersten Wert zu vertrauen');
    $_SERVER['HTTP_X_FORWARDED_FOR']='192.0.2.20';
    sbkt_check(sbk_client_ip()==='192.0.2.20'&&sbk_excluded(),'Echte ausgeschlossene Adresse wird erkannt');
    sbkt_check(is_wp_error(sbk_permission($r)),'IP-Ausschluss sperrt Browser- und Zeitmessung');
    foreach (['192.0.2.20/32','bad,192.0.2.20',str_repeat('1',1100)] as $bad) {
        $_SERVER['HTTP_X_FORWARDED_FOR']=$bad;sbkt_check(sbk_client_ip()==='192.0.2.2','Manipulierte Proxy-Kette ignoriert');
    }
    $_SERVER['HTTP_X_FORWARDED_FOR']='192.0.2.20';$before=sbkt_sum('server_views');$function();
    sbkt_check(sbkt_sum('server_views')===$before,'IP-Ausschluss greift vor Serverzählung');
    $GLOBALS['sbkt_settings']['ip_blacklist']=[];$GLOBALS['sbkt_settings']['trusted_proxies']=[];unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    sbkt_check(sbk_services()===[],'YouTube/Vimeo standardmäßig nicht angeboten');
    sbkt_check(do_shortcode('[seitenblick_video anbieter="youtube" id="abcdefghijk"]')==='','Ausgeschalteter Videodienst nicht eingebunden');
    $flags=['statistics'=>false,'external'=>true,'marketing'=>false];
    sbkt_check(is_wp_error(sbk_consent(sbkt_request(['action'=>'save','choices'=>$flags,'adult'=>true,'policy'=>sbk_policy()]))),'Nicht angebotene Kategorie serverseitig nicht freigebbar');
    $GLOBALS['sbkt_settings']['enabled_services']=['youtube'];
    sbkt_check(isset(sbk_services()['youtube'])&&!isset(sbk_services()['vimeo']),'Nur ausgewählter Dienst angeboten');
    $cookie=sbk_encode_consent(['statistics'=>true,'external'=>false,'marketing'=>false],true);
    $GLOBALS['sbkt_settings']['texts']=['dashboard_title'=>'Andere Überschrift'];
    sbkt_check(sbk_decode_consent($cookie)!==null,'Dashboard-Text ändert Einwilligung nicht');
    $GLOBALS['sbkt_settings']['texts']['intro']='Neuer Einwilligungshinweis';
    sbkt_check(sbk_decode_consent($cookie)===null,'Einwilligungstext ändert Richtlinie und verlangt neue Auswahl');
    $GLOBALS['sbkt_settings']['texts']=[];$GLOBALS['sbkt_settings']['enabled_services']=[];
    $register=function($services){$services['local-test']=['label'=>'Test','category'=>'marketing','description'=>'Test','privacy'=>'https://example.org/privacy','handles'=>['test-local']];return $services;};
    add_filter('seitenblick_services',$register);
    $tag=apply_filters('script_loader_tag',$local,'test-local',home_url('/local.js'));
    sbkt_check(str_contains($tag,'application/x-seitenblick')&&!str_contains($tag,' src='),'Ausgeschalteter registrierter lokaler Dienst bleibt inert');
    remove_filter('seitenblick_services',$register);
    $cfg=sbk_consent_config();
    sbkt_check(!isset($cfg['ip_blacklist'],$cfg['trusted_proxies'])&&!str_contains(wp_json_encode($cfg),'192.0.2.20'),'Öffentliche Konfiguration ohne private IP-Einstellungen');
    $find=sbk_audit_markup('<a href="https://youtube.com/watch?v=x">Quelle</a><img src="'.home_url('/media/test.webp').'">');
    sbkt_check($find===[],'Normale Fremdlinks und lokale Bilder keine Einbettung');
    foreach (['<iframe src="https://www.youtube.com/embed/abcdefghijk"></iframe>','<!-- wp:embed {"url":"https://youtu.be/abcdefghijk"} /-->','[seitenblick_video anbieter="youtube" id="abcdefghijk"]',"https://youtu.be/abcdefghijk\n"] as $html) {
        $find=sbk_audit_markup($html);sbkt_check(count($find)>0&&$find[0]['id']==='youtube','YouTube-Einbettung/Shortcode/WordPress-Embed erkannt');
    }
    $find=sbk_audit_markup('<iframe src="//player.vimeo.com/video/1234"></iframe>');
    sbkt_check($find[0]['id']==='vimeo','Vimeo erkannt');
    $find=sbk_audit_markup('<script src="https://youtube.com.evil.example/x.js?secret=1"></script>');
    sbkt_check($find[0]['id']==='other'&&$find[0]['host']==='youtube.com.evil.example','Fremdhost sicher eingeordnet und nur Host ausgegeben');
    sbkt_check(sbk_audit_markup('<iframe src="javascript:alert(1)"></iframe>')===[],'Nicht-Netzwerkadresse nicht als Anbieter erfasst');
    $initialPolicy=sbk_policy();
    $donation=sbk_donation_html();
    sbkt_check(str_contains(html_entity_decode($donation,ENT_QUOTES,'UTF-8'),'href="https://www.paypal.com/donate/?hosted_button_id=G72QV8CQAL6DY&locale.x=de_DE"'),'Exakt freigegebenes PayPal-Spendenziel');
    sbkt_check(str_contains($donation,'target="_blank"')&&str_contains($donation,'noopener noreferrer')&&str_contains($donation,'referrerpolicy="no-referrer"'),'Spendenlink ohne Referrer und Zugriff auf Ursprungstab');
    sbkt_check(!preg_match('/<(script|img|iframe|form)\b/i',$donation),'Keine PayPal-Ressourcen oder Zahlungsformulare eingebunden');
    $parsed=sbk_parse_settings(array_replace($base,['donation'=>['enabled'=>'1','title'=>'Danke <img src=x onerror=alert(1)>','description'=>'Freiwillig','button'=>'Unterstützen']]));
    sbkt_check(is_array($parsed)&&$parsed['donation']['enabled']===true&&!str_contains($parsed['donation']['title'],'<img'),'Spendentexte sicher in vorhandene Einstellungen integriert');
    $GLOBALS['sbkt_settings']['donation']=['enabled'=>true,'title'=>'<img src=x onerror=alert(1)>','description'=>'Text','button'=>'PayPal'];
    sbkt_check(!str_contains(sbk_donation_html(),'<img'),'Spendentexte nicht als ausführbares HTML ausgegeben');
    sbkt_check(sbk_policy()===$initialPolicy,'Spendenänderung invalidiert Statistik-Einwilligung nicht');
    $GLOBALS['sbkt_settings']['donation']['enabled']=false;
    sbkt_check(sbk_donation_html()==='','Spendenhinweis ausblendbar');
    sbkt_check(!isset(sbk_consent_config()['donation']),'Kein Spendenhinweis in öffentlicher Consent-Konfiguration');
    foreach ([[],['title'=>[]],['enabled'=>'yes'],['button'=>str_repeat('x',101)],['url'=>'https://evil.example/'],['title'=>'Text','description'=>[]]] as $case) {
        if ($case===[]) { continue; }
        sbkt_check(is_wp_error(sbk_parse_donation($case)),'Ungültige Spendeneinstellungen vor Speicherung abgewiesen');
    }
    $parsed=sbk_parse_donation(['title'=>'','description'=>'','button'=>'']);
    sbkt_check($parsed['enabled']===false&&$parsed['title']===sbk_donation_defaults()['title'],'Ausblenden und Rückkehr zu Standard-Spendentexten');
    unset($GLOBALS['sbkt_settings']['donation']);
    $columns=implode(' ',$wpdb->get_col("SHOW COLUMNS FROM $totals"));
    sbkt_check(!preg_match('/visitor|address|cookie|session|user|referrer|agent/i',$columns),'Summenschema ohne Personen-/Sitzungs-/Referrerfelder');
    echo "PASS $checks Prüfungen; nur temporäre Tabellen, keine produktiven Änderungen.\n";
} finally {
    $wpdb->query("DROP TEMPORARY TABLE IF EXISTS $totals");
    $wpdb->query("DROP TEMPORARY TABLE IF EXISTS $guard");
    $wpdb->prefix=$prefix;
    ob_end_flush();
}
