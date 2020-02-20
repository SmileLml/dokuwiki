<?php
/**
 * 初始化DokuWiki所需的一些默认值
 */


/**
 * timing Dokuwiki execution
 *
 * @param integer $start
 *
 * @return mixed
 */
function delta_time($start = 0)
{
    return microtime(true) - ((float)$start);
}

// 开始时间
define('DOKU_START_TIME', delta_time());

global $config_cascade;
$config_cascade = array();

// 如果可用，请加载预加载配置文件
$preload = fullpath(dirname(__FILE__)) . '/preload.php';
if (file_exists($preload)) include($preload);

// 主目录 已定义
if (!defined('DOKU_INC')) define('DOKU_INC', fullpath(dirname(__FILE__) . '/../') . '/');
// 插件目录
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

// 定义配置路径（打包者可能希望将其更改为/etc/dokuwiki/）已定义
if (!defined('DOKU_CONF')) define('DOKU_CONF', DOKU_INC . 'conf/');

// 检查错误报告覆盖或将错误报告设置为合理值
if (!defined('DOKU_E_LEVEL') && file_exists(DOKU_CONF . 'report_e_all')) {
    define('DOKU_E_LEVEL', E_ALL);
}
if (!defined('DOKU_E_LEVEL')) {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
} else {
    error_reporting(DOKU_E_LEVEL);
}

// avoid caching issues #1594
header('Vary: Cookie');

// init memory caches
global $cache_revinfo;
$cache_revinfo = array();
global $cache_wikifn;
$cache_wikifn = array();
global $cache_cleanid;
$cache_cleanid = array();
global $cache_authname;
$cache_authname = array();
global $cache_metadata;
$cache_metadata = array();

// always include 'inc/config_cascade.php'
// previously in preload.php set fields of $config_cascade will be merged with the defaults
include(DOKU_INC . 'inc/config_cascade.php');

//prepare config array()
global $conf;
$conf = array();

// load the global config file(s)
foreach (array('default', 'local', 'protected') as $config_group) {
    if (empty($config_cascade['main'][$config_group])) continue;
    foreach ($config_cascade['main'][$config_group] as $config_file) {
        if (file_exists($config_file)) {
            include($config_file);
        }
    }
}

//prepare license array()
global $license;
$license = array();

// load the license file(s)
foreach (array('default', 'local') as $config_group) {
    if (empty($config_cascade['license'][$config_group])) continue;
    foreach ($config_cascade['license'][$config_group] as $config_file) {
        if (file_exists($config_file)) {
            include($config_file);
        }
    }
}

// set timezone (as in pre 5.3.0 days)
date_default_timezone_set(@date_default_timezone_get());
// define baseURL
//  string(1) "/"
if (!defined('DOKU_REL')) define('DOKU_REL', getBaseURL(false));
// http://localhost/
if (!defined('DOKU_URL')) define('DOKU_URL', getBaseURL(true));
//  string(1) "/"
if (!defined('DOKU_BASE')) {
    if ($conf['canonical']) {
        define('DOKU_BASE', DOKU_URL);
    } else {
        define('DOKU_BASE', DOKU_REL);
    }
}
// define whitespace
if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");

// 定义cookie和会话ID，在配置securecookie时附加服务器端口FS#1664
if (!defined('DOKU_COOKIE')) define('DOKU_COOKIE', 'DW' . md5(DOKU_REL . (($conf['securecookie']) ? $_SERVER['SERVER_PORT'] : '')));

// define 主脚本 doku.php
if (!defined('DOKU_SCRIPT')) define('DOKU_SCRIPT', 'doku.php');

// DEPRECATED, use tpl_basedir() instead 初始模版目录 /lib/tpl/dokuwiki/
if (!defined('DOKU_TPL')) define('DOKU_TPL',
    DOKU_BASE . 'lib/tpl/' . $conf['template'] . '/');

// DEPRECATED, use tpl_incdir() instead 初始模版目录 /lib/tpl/dokuwiki/
if (!defined('DOKU_TPLINC')) define('DOKU_TPLINC',
    DOKU_INC . 'lib/tpl/' . $conf['template'] . '/');

// 使会话重写符合XHTML
@ini_set('arg_separator.output', '&amp;');

// 确保全局zlib不会干扰
@ini_set('zlib.output_compression', 'off');

// 增加PCRE回溯限制
@ini_set('pcre.backtrack_limit', '20971520');

// 如果支持，启用gzip压缩
$conf['gzip_output'] &= (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false);

global $ACT;
// 设置输出缓冲区
if ($conf['gzip_output'] &&
    !defined('DOKU_DISABLE_GZIP_OUTPUT') &&
    function_exists('ob_gzhandler') &&
    // Disable compression when a (compressed) sitemap might be delivered
    // See https://bugs.dokuwiki.org/index.php?do=details&task_id=2576
    $ACT != 'sitemap') {
    ob_start('ob_gzhandler');
}

// 初始化 session
if (!headers_sent() && !defined('NOSESSION')) {
    // session_name DokuWiki
    if (!defined('DOKU_SESSION_NAME')) define('DOKU_SESSION_NAME', "DokuWiki");
    // 0
    if (!defined('DOKU_SESSION_LIFETIME')) define('DOKU_SESSION_LIFETIME', 0);
    // SESSION_PATH /
    if (!defined('DOKU_SESSION_PATH')) {
        $cookieDir = empty($conf['cookiedir']) ? DOKU_REL : $conf['cookiedir'];
        define('DOKU_SESSION_PATH', $cookieDir);
    }
    // DOKU_SESSION_DOMAIN ''
    if (!defined('DOKU_SESSION_DOMAIN')) define('DOKU_SESSION_DOMAIN', '');
    // 开启 session
    init_session();
    /**
     * $_SESSION[DOKU_COOKIE]
     * array(3) {
     *  ["auth"]=> array(5) {
     *      ["user"]=> string(8) "smilelml"
     *      ["pass"]=> string(40) "d0b780920e36f6ef076704e5932eca538940cbb6"
     *      ["buid"]=> string(32) "8a99022eed60778e3d33aa9da8d27786"
     *      ["info"]=> array(4) {
     *          ["pass"]=> string(34) "$1$jUxAjuNg$7jaEq9DGxSeE/WgMxvGzs."
     *          ["name"]=> string(12) "liumingliang"
     *          ["mail"]=> string(19) "liumingliang@qie.tv"
     *          ["grps"]=> array(2) {
     *              [0]=> string(5) "admin"
     *              [1]=> string(4) "user"
     *          }
     *      }
     *      ["time"]=> int(1582183656)
     *  }
     *  ["bc"]=> array(2) {
     *      ["sidebar"]=> string(7) "sidebar"
     *      ["start"]=> string(5) "start"
     *  }
     *  ["translationlc"]=> string(0) ""
     * }
     */
    // load left over messages
    if (isset($_SESSION[DOKU_COOKIE]['msg'])) {
        $MSG = $_SESSION[DOKU_COOKIE]['msg'];
        unset($_SESSION[DOKU_COOKIE]['msg']);
    }
}
// 不要让Cookie干扰请求变量
$_REQUEST = array_merge($_GET, $_POST);

// we don't want a purge URL to be digged
if (isset($_REQUEST['purge']) && !empty($_SERVER['HTTP_REFERER'])) unset($_REQUEST['purge']);

// 预先计算文件创建模式
init_creationmodes();

// 做真实的路径并检查它们
init_paths();
init_files();

// 设置插件控制器类（可以在preload.php中覆盖）
$plugin_types = array('auth', 'admin', 'syntax', 'action', 'renderer', 'helper', 'remote');
global $plugin_controller_class, $plugin_controller;
if (empty($plugin_controller_class)) $plugin_controller_class = 'Doku_Plugin_Controller';

// 加载插件
require_once(DOKU_INC . 'vendor/autoload.php');
require_once(DOKU_INC . 'inc/load.php');

// 禁用gzip（如果不可用）
define('DOKU_HAS_BZIP', function_exists('bzopen')); // true
define('DOKU_HAS_GZIP', function_exists('gzopen')); // true

if ($conf['compression'] == 'bz2' && !DOKU_HAS_BZIP) {
    $conf['compression'] = 'gz';
}
if ($conf['compression'] == 'gz' && !DOKU_HAS_GZIP) {
    $conf['compression'] = 0;
}

// input handle class
global $INPUT;
$INPUT = new Input();

// initialize plugin controller
$plugin_controller = new $plugin_controller_class();

// initialize the event handler
global $EVENT_HANDLER;
$EVENT_HANDLER = new Doku_Event_Handler();

$local = $conf['lang'];
trigger_event('INIT_LANG_LOAD', $local, 'init_lang', true);


// setup authentication system
if (!defined('NOSESSION')) {
    auth_setup();
}

// setup mail system
mail_setup();

/**
 * Initializes the session
 *
 * Makes sure the passed session cookie is valid, invalid ones are ignored an a new session ID is issued
 *
 * @link http://stackoverflow.com/a/33024310/172068
 * @link http://php.net/manual/en/session.configuration.php#ini.session.sid-length
 */
function init_session()
{
    global $conf;
    session_name(DOKU_SESSION_NAME);
    session_set_cookie_params(DOKU_SESSION_LIFETIME, DOKU_SESSION_PATH, DOKU_SESSION_DOMAIN, ($conf['securecookie'] && is_ssl()), true);

    // make sure the session cookie contains a valid session ID
    if (isset($_COOKIE[DOKU_SESSION_NAME]) && !preg_match('/^[-,a-zA-Z0-9]{22,256}$/', $_COOKIE[DOKU_SESSION_NAME])) {
        unset($_COOKIE[DOKU_SESSION_NAME]);
    }

    session_start();
}


/**
 * Checks paths from config file
 */
function init_paths()
{
    global $conf;

    $paths = array('datadir' => 'pages',
        'olddir' => 'attic',
        'mediadir' => 'media',
        'mediaolddir' => 'media_attic',
        'metadir' => 'meta',
        'mediametadir' => 'media_meta',
        'cachedir' => 'cache',
        'indexdir' => 'index',
        'lockdir' => 'locks',
        'tmpdir' => 'tmp');

    foreach ($paths as $c => $p) {
        $path = empty($conf[$c]) ? $conf['savedir'] . '/' . $p : $conf[$c];
        $conf[$c] = init_path($path);
        if (empty($conf[$c]))
            nice_die("The $c ('$p') at $path is not found, isn't accessible or writable.
                You should check your config and permission settings.
                Or maybe you want to <a href=\"install.php\">run the
                installer</a>?");
    }

    // path to old changelog only needed for upgrading
    $conf['changelog_old'] = init_path((isset($conf['changelog'])) ? ($conf['changelog']) : ($conf['savedir'] . '/changes.log'));
    if ($conf['changelog_old'] == '') {
        unset($conf['changelog_old']);
    }
    // hardcoded changelog because it is now a cache that lives in meta
    $conf['changelog'] = $conf['metadir'] . '/_dokuwiki.changes';
    $conf['media_changelog'] = $conf['metadir'] . '/_media.changes';
}

/**
 * Load the language strings
 *
 * @param string $langCode language code, as passed by event handler
 */
function init_lang($langCode)
{
    //prepare language array
    global $lang, $config_cascade;
    $lang = array();

    //load the language files
    require(DOKU_INC . 'inc/lang/en/lang.php');
    foreach ($config_cascade['lang']['core'] as $config_file) {
        if (file_exists($config_file . 'en/lang.php')) {
            include($config_file . 'en/lang.php');
        }
    }

    if ($langCode && $langCode != 'en') {
        if (file_exists(DOKU_INC . "inc/lang/$langCode/lang.php")) {
            require(DOKU_INC . "inc/lang/$langCode/lang.php");
        }
        foreach ($config_cascade['lang']['core'] as $config_file) {
            if (file_exists($config_file . "$langCode/lang.php")) {
                include($config_file . "$langCode/lang.php");
            }
        }
    }
}

/**
 * 检查某些文件是否存在，如果缺少则创建它们。
 */
function init_files()
{
    global $conf;

    $files = array($conf['indexdir'] . '/page.idx');

    foreach ($files as $file) {
        if (!file_exists($file)) {
            $fh = @fopen($file, 'a');
            if ($fh) {
                fclose($fh);
                if (!empty($conf['fperm'])) chmod($file, $conf['fperm']);
            } else {
                nice_die("$file is not writable. Check your permissions settings!");
            }
        }
    }
}

/**
 * Returns absolute path
 *
 * 这首先尝试给定的路径，然后签入DOKU_INC。
 * 还要检查目录的可访问性。
 *
 * @param string $path
 *
 * @return bool|string
 * @author Andreas Gohr <andi@splitbrain.org>
 *
 */
function init_path($path)
{
    // check existence
    $p = fullpath($path);
    if (!file_exists($p)) {
        $p = fullpath(DOKU_INC . $path);
        if (!file_exists($p)) {
            return '';
        }
    }

    // check writability
    if (!@is_writable($p)) {
        return '';
    }

    // check accessability (execute bit) for directories
    if (@is_dir($p) && !file_exists("$p/.")) {
        return '';
    }

    return $p;
}

/**
 * 设置内部配置值fperm和dperm，设置后，
 * 将用于更改新创建的目录的权限或
 * 带有chmod的文件。 考虑系统umask的影响
 * 仅在需要时设置值。
 */
function init_creationmodes()
{
    global $conf;

    // Legacy support for old umask/dmask scheme
    unset($conf['dmask']);
    unset($conf['fmask']);
    unset($conf['umask']);
    unset($conf['fperm']);
    unset($conf['dperm']);

    // get system umask, fallback to 0 if none available
    $umask = @umask();
    if (!$umask) $umask = 0000;

    // check what is set automatically by the system on file creation
    // and set the fperm param if it's not what we want
    $auto_fmode = 0666 & ~$umask;
    if ($auto_fmode != $conf['fmode']) $conf['fperm'] = $conf['fmode'];

    // check what is set automatically by the system on file creation
    // and set the dperm param if it's not what we want
    $auto_dmode = $conf['dmode'] & ~$umask;
    if ($auto_dmode != $conf['dmode']) $conf['dperm'] = $conf['dmode'];
}

/**
 * Returns the full absolute URL to the directory where
 * DokuWiki is installed in (includes a trailing slash)
 *
 * !! Can not access $_SERVER values through $INPUT
 * !! here as this function is called before $INPUT is
 * !! initialized.
 *
 * @param null|string $abs
 *
 * @return string
 * @author Andreas Gohr <andi@splitbrain.org>
 *
 */
function getBaseURL($abs = null)
{
    global $conf;
    //if canonical url enabled always return absolute
    if (is_null($abs)) $abs = $conf['canonical'];

    if (!empty($conf['basedir'])) {
        $dir = $conf['basedir'];
    } elseif (substr($_SERVER['SCRIPT_NAME'], -4) == '.php') {
        $dir = dirname($_SERVER['SCRIPT_NAME']);
    } elseif (substr($_SERVER['PHP_SELF'], -4) == '.php') {
        $dir = dirname($_SERVER['PHP_SELF']);
    } elseif ($_SERVER['DOCUMENT_ROOT'] && $_SERVER['SCRIPT_FILENAME']) {
        $dir = preg_replace('/^' . preg_quote($_SERVER['DOCUMENT_ROOT'], '/') . '/', '',
            $_SERVER['SCRIPT_FILENAME']);
        $dir = dirname('/' . $dir);
    } else {
        $dir = '.'; //probably wrong
    }

    $dir = str_replace('\\', '/', $dir);             // bugfix for weird WIN behaviour
    $dir = preg_replace('#//+#', '/', "/$dir/");     // ensure leading and trailing slashes

    //handle script in lib/exe dir
    $dir = preg_replace('!lib/exe/$!', '', $dir);

    //handle script in lib/plugins dir
    $dir = preg_replace('!lib/plugins/.*$!', '', $dir);

    //finish here for relative URLs
    if (!$abs) return $dir;

    //use config option if available, trim any slash from end of baseurl to avoid multiple consecutive slashes in the path
    if (!empty($conf['baseurl'])) return rtrim($conf['baseurl'], '/') . $dir;

    //split hostheader into host and port
    if (isset($_SERVER['HTTP_HOST'])) {
        $parsed_host = parse_url('http://' . $_SERVER['HTTP_HOST']);
        $host = isset($parsed_host['host']) ? $parsed_host['host'] : null;
        $port = isset($parsed_host['port']) ? $parsed_host['port'] : null;
    } elseif (isset($_SERVER['SERVER_NAME'])) {
        $parsed_host = parse_url('http://' . $_SERVER['SERVER_NAME']);
        $host = isset($parsed_host['host']) ? $parsed_host['host'] : null;
        $port = isset($parsed_host['port']) ? $parsed_host['port'] : null;
    } else {
        $host = php_uname('n');
        $port = '';
    }

    if (is_null($port)) {
        $port = '';
    }

    if (!is_ssl()) {
        $proto = 'http://';
        if ($port == '80') {
            $port = '';
        }
    } else {
        $proto = 'https://';
        if ($port == '443') {
            $port = '';
        }
    }

    if ($port !== '') $port = ':' . $port;

    return $proto . $host . $port . $dir;
}

/**
 * Check if accessed via HTTPS
 *
 * Apache leaves ,$_SERVER['HTTPS'] empty when not available, IIS sets it to 'off'.
 * 'false' and 'disabled' are just guessing
 *
 * @returns bool true when SSL is active
 */
function is_ssl()
{
    // check if we are behind a reverse proxy
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        if ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return true;
        } else {
            return false;
        }
    }
    if (!isset($_SERVER['HTTPS']) ||
        preg_match('/^(|off|false|disabled)$/i', $_SERVER['HTTPS'])) {
        return false;
    } else {
        return true;
    }
}

/**
 * checks it is windows OS
 * @return bool
 */
function isWindows()
{
    return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? true : false;
}

/**
 * print a nice message even if no styles are loaded yet.
 *
 * @param integer|string $msg
 */
function nice_die($msg)
{
    echo <<<EOT
<!DOCTYPE html>
<html>
<head><title>DokuWiki Setup Error</title></head>
<body style="font-family: Arial, sans-serif">
    <div style="width:60%; margin: auto; background-color: #fcc;
                border: 1px solid #faa; padding: 0.5em 1em;">
        <h1 style="font-size: 120%">DokuWiki Setup Error</h1>
        <p>$msg</p>
    </div>
</body>
</html>
EOT;
    if (defined('DOKU_UNITTEST')) {
        throw new RuntimeException('nice_die: ' . $msg);
    }
    exit(1);
}

/**
 * A realpath() replacement
 *
 * This function behaves similar to PHP's realpath() but does not resolve
 * symlinks or accesses upper directories
 *
 * @param string $path
 * @param bool $exists
 *
 * @return bool|string
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author <richpageau at yahoo dot co dot uk>
 * @link   http://php.net/manual/en/function.realpath.php#75992
 *
 */
function fullpath($path, $exists = false)
{
    static $run = 0;
    $root = '';
    $iswin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || @$GLOBALS['DOKU_UNITTEST_ASSUME_WINDOWS']);

    // find the (indestructable) root of the path - keeps windows stuff intact
    if ($path{0} == '/') {
        $root = '/';
    } elseif ($iswin) {
        // match drive letter and UNC paths
        if (preg_match('!^([a-zA-z]:)(.*)!', $path, $match)) {
            $root = $match[1] . '/';
            $path = $match[2];
        } else if (preg_match('!^(\\\\\\\\[^\\\\/]+\\\\[^\\\\/]+[\\\\/])(.*)!', $path, $match)) {
            $root = $match[1];
            $path = $match[2];
        }
    }
    $path = str_replace('\\', '/', $path);

    // if the given path wasn't absolute already, prepend the script path and retry
    if (!$root) {
        $base = dirname($_SERVER['SCRIPT_FILENAME']);
        $path = $base . '/' . $path;
        if ($run == 0) { // avoid endless recursion when base isn't absolute for some reason
            $run++;
            return fullpath($path, $exists);
        }
    }
    $run = 0;

    // canonicalize
    $path = explode('/', $path);
    $newpath = array();
    foreach ($path as $p) {
        if ($p === '' || $p === '.') continue;
        if ($p === '..') {
            array_pop($newpath);
            continue;
        }
        array_push($newpath, $p);
    }
    $finalpath = $root . implode('/', $newpath);

    // check for existence when needed (except when unit testing)
    if ($exists && !defined('DOKU_UNITTEST') && !file_exists($finalpath)) {
        return false;
    }
    return $finalpath;
}

