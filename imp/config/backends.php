<?php
/**
 * This file specifies which mail servers people using your installation of
 * IMP can login to.
 *
 * IMPORTANT: Local overrides should be placed in backends.local.php, or
 * backends-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 *
 * Properties that can be set for each server:
 *
 * disabled: (boolean) If true, the config entry is disabled.
 *
 * name: (string) This is the name displayed in the server list on the login
 *       screen.
 *
 * hostspec: (string) The hostname/IP address of the mail server to connect to.
 *
 * hordeauth: (mixed) One of the following values:
 *              true - IMP will attempt to use the user's existing credentials
 *                     (the username/password they used to log in to Horde) to
 *                     login to this source.
 *              false - Everything after and including the first @ in the
 *                      username will be stripped off before attempting
 *                      authentication. (DEFAULT)
 *              'full' - The username will be used unmodified.
 *
 * protocol: (string) The server protocol. Either 'pop' or 'imap'.
 *
 *           'imap' requires a IMAP4rev1 (RFC 3501) compliant server
 *           (DEFAULT).
 *
 *           'pop' requires a POP3 (RFC 1939) compliant server. All folder
 *           options will be automatically turned off (POP3 does
 *           not support folders).  Other advanced functions will also be
 *           disabled (e.g. caching, searching, sorting).
 *
 * secure: (mixed) Transport security used for connection to the server. One
 *         of the following values:
 *           'ssl' - Use SSL to connect to the server.
 *           'tls' - Use TLS to connect to the server.
 *           false - Do not use any encryption (DEFAULT).
 *
 *         The 'ssl' and 'tls' options will only work if you've compiled PHP
 *         with SSL support and the mail server supports secure connections.
 *
 *         The use of 'ssl' is STRONGLY DISCOURAGED. If a secure connection
 *         is needed, 'tls' should be used and the connection should be made
 *         to the base protocol port (110 for POP3, 143 for IMAP).
 *
 * port: (integer) The port that the mail service/protocol you selected runs
 *       on. Default values:
 *         imap (unsecure or w/TLS):  143
 *         imap (w/SSL):              993 (DISCOURAGED - use TLS on port 143)
 *         pop (unsecure or w/TLS):   110
 *         pop (w/SSL):               995 (DISCOURAGED - use TLS on port 110)
 *
 * maildomain: (string) What to put after the @ when sending mail. This
 *             setting is generally useful when the sending host is different
 *             from the mail receiving host. This setting will also be used to
 *             complete unqualified addresses when composing mail.
 *             E.g. If you want all mail to look like 'From:
 *             user@example.com', set maildomain to 'example.com'.
 *
 * smtphost: (string) If specified, and $conf['mailer']['type'] is set to
 *           'smtp', IMP will use this host for outbound SMTP connections.
 *           This value overrides any existing
 *           $conf['mailer']['params']['host'] value at runtime.
 *
 * smtpport: (integer) If specified, and $conf['mailer']['type'] is set to
 *           'smtp', IMP will use this port for outbound SMTP connections.
 *           This value overrides any existing
 *           $conf['mailer']['params']['port'] value at runtime.
 *
 * admin: (array) Use this if you want to enable mailbox management for
 *        administrators via Horde's user administration interface.  The
 *        mailbox management gets enabled if you let IMP handle the Horde
 *        authentication with the 'application' authentication driver.  Your
 *        IMAP server needs to support mailbox management via IMAP commands.
 *        Do not define this value if you do not want mailbox management.
 *
 *        The following parameters are available (defined in 'params'):
 *        'admin_user' - The admin user.
 *        'admin_password' - The admin user's password.
 *        'userhierarchy' - The hierarchy where user mailboxes are stored.
 *
 * acl: (boolean) Set to true if you want to use Access Control Lists (mailbox
 *      sharing). Set to false to disable (DEFAULT). Not all IMAP servers
 *      support this feature.
 *
 * cache: (mixed) Enables caching for the server. This requires configuration
 *        of a Horde_Cache driver in Horde. Will be disabled on any empty
 *        value and enabled on any non-false value.
 *
 *        Caching is HIGHLY RECOMMENDED. There is no reason not to cache if
 *        the IMAP server supports the CONDSTORE and/or QRESYNC IMAP
 *        extensions. If the server does not support these extensions, any
 *        flags changed by another mail agent while the IMP session is active
 *        will not be updated. If IMP will be the exclusive method of
 *        accessing the IMAP server, or you do not care about this behavior,
 *        caching should be enabled on these servers.
 *
 *        The following optional configuration items are available:
 *          'lifetime' - (integer) The lifetime, in seconds, of the cached
 *                       data.
 *          'slicesize' - (integer) The number of messages stored in each
 *                        cache slice.  The default should be fine for most
 *                        installations.
 *
 * 'debug' - (string) If set, will output debug information from the IMAP
 *           library. The value can be any PHP supported wrapper that can be
 *           opened via fopen(). This setting should not be enabled by default
 *           on production servers, since the log file will quickly grow very
 *           large.
 *
 *           For example, to output to a file, provide the full path to the
 *           file (a bare string is interpreted by PHP to be a filename). This
 *           file must be writable by the PHP process.
 *
 *           To restrict logging to a certain user ('foo'), and to log this
 *           output to the file '/tmp/imaplog', the following can be used:
 *
 *             (($GLOBALS['registry']->getAuth() == 'foo') ? '/tmp/imaplog' : false)
 *
 * quota: (array) Use this if you want to display a user's quota status on
 *        various IMP pages. Set to an empty value to disable quota status
 *        (DEFAULT).
 *
 *        The entry is configured as follows:
 *
 *          'quota' => array(
 *              // Driver name: see below
 *              'driver' => [string] (REQUIRED),
 *              'params' => array(
 *                  // True if you want to hide quota output when the server
 *                  // reports an unlimited quota.
 *                  'hide_when_unlimited' => true | false,
 *
 *                  // What storage unit the quota messages should be
 *                  // displayed in.
 *                  'unit' => 'GB' | 'MB' (DEFAULT) | 'KB',
 *
 *                  // Output format: see below.
 *                  'format' => array(),
 *
 *                  // Additional driver parameters: see below.
 *              )
 *          )
 *
 *        'driver'/'params' can be the following:
 *
 *           DRIVER: 'command'
 *                   Use the UNIX quota command to handle quotas.
 *           PARAMS: 'quota_path' - [string] Path to the quota binary
 *                                  (REQUIRED)
 *                   'grep_path' - [string] Path to the grep binary (REQUIRED)
 *                   'partition' - [string] If all user mailboxes are on a
 *                                 single partition, the partition label. By
 *                                 default, quota will determine quota
 *                                 information using the user's home directory
 *                                 value.
 *
 *           DRIVER: 'hook'
 *                   Use the quota hook to handle quotas (see
 *                   imp/config/hooks.php).
 *           PARAMS: Any parameters defined here will be passed to the quota
 *                   hook function.
 *
 *           DRIVER: 'imap'
 *                   Use the IMAP QUOTA extension to handle quotas.
 *                   You must be connecting to a IMAP server capable of the
 *                   QUOTAROOT command to use this driver. This is the
 *                   RECOMMENDED way of handling quotas if the IMAP server
 *                   supports it.
 *           PARAMS: NONE
 *
 *           DRIVER: 'maildir'
 *                   Use Maildir++ quota files to handle quotas.
 *           PARAMS: 'msg_count' - [boolean] Display information on the
 *                                 message limit rather than the storage
 *                                 limit? The storage limit information is
 *                                 displayed by default.
 *                   'path' - [string] The path to the user's Maildir
 *                            directory. You may use the two-character
 *                            sequence "~U" to represent the user's account
 *                            name, and the actual username will be
 *                            substituted in that location. E.g.,
 *                            '/home/~U/Maildir/' or '/var/mail/~U/Maildir/'.
 *
 *           DRIVER: 'mdaemon'
 *                    Use Mdaemon servers to handle quotas.
 *           PARAMS: 'app_location' - [string] Location of the application.
 *
 *           DRIVER: 'mercury32'
 *                   Use Mercury/32 servers to handle quotas.
 *           PARAMS: 'mail_user_folder' - [string] The path to folder mail
 *                   mercury.
 *
 *           DRIVER: 'sql'
 *                   Use arbitrary SQL queries to handle quotas.
 *           PARAMS: 'query_quota' - (string) SQL query which returns single
 *                   row/column with user quota (in bytes). %u is replaced
 *                   with current user name, %U with the user name without the
 *                   domain part, %d with the domain.
 *                   'query_used' - (string) SQL query which returns single
 *                   row/column with user used space (in bytes). Placeholders
 *                   are the same like in query_quota.
 *
 *                   Additionally, the driver takes SQL connection parameters
 *                   'phptype', 'hostspec',' 'username', 'password', and
 *                   'database'. See horde/config/conf.php for further
 *                   information on these parameters. If using the Horde DB,
 *                   these parameters can be found in the
 *                   $GLOBALS['conf']['sql'] variable and may be merged into
 *                   the parameter configuration like this:
 *
 *                     'params' => array_merge(
 *                         $GLOBALS['conf']['sql'],
 *                         array(
 *                             'query_quota' => [...],
 *                             'query_used' => [...],
 *                         )
 *                     )
 *
 *        The optional 'format' parameter is supported by all drivers and
 *        specifies the formats of the quota messages in the user
 *        interface. The parameter must be specified as a hash with the four
 *        possible elements 'long', 'short', 'nolimit_long', and
 *        'nolimit_short'. The strings will be passed through sprintf().
 *        These are the built-in default values, though they may appear
 *        differently in some translations ([UNIT] will be replaced with the
 *        value of the 'unit' parameter):
 *          'long'          - Quota status: %.2f [UNIT] / %.2f [UNIT] (%.2f%%)
 *          'nolimit_long'  - Quota status: %.2f [UNIT] / NO LIMIT
 *          'short'         - %.0f%% of %.0f [UNIT]
 *          'nolimit_short' - %.0f [UNIT]
 *
 *
 * *** The following options should NOT be set unless you REALLY know what ***
 * *** you are doing! FOR MOST PEOPLE, AUTO-DETECTION OF THESE PARAMETERS  ***
 * *** (the default if the parameters are not set) SHOULD BE USED!         ***
 *
 * capability_ignore: (array) A list of IMAP capabilites to ignore, even if
 *                    they are supported on the server. This capability names
 *                    should be in all capitals. This option may be useful,
 *                    for example, if it is known that a certain capability is
 *                    buggy on the given server.  Otherwise, all available
 *                    and supported IMAP capabilities will be (and should be)
 *                    used. (IMAP only)
 *
 * comparator: (string) The search comparator to use instead of the default
 *             IMAP server comparator. See RFC 4790 [3.1] - "collation-id" -
 *             for the format. Your IMAP server must support the I18NLEVEL
 *             extension for this setting to have an effect. By default,
 *             the server default comparator is used. (IMAP only)
 *
 * id: (array) Send ID information to the IMAP server. This must be an array
 *     with the keys being the fields to send and the values being the
 *     associated values. Your IMAP server must support the ID extension for
 *     this setting to have an effect. See RFC 2971 [3.3] for a list of
 *     defined field values. (IMAP only)
 *
 * lang: (array) A list of languages (in priority order) to be used to display
 *       human readable messages returned by the IMAP server. Your IMAP server
 *       must support the LANGUAGE extensions for this setting to have an
 *       effect. By default, IMAP messages are output in the IMAP server
 *       default language. (IMAP only)
 *
 * namespace: (array) The list of namespaces that exist on the server. The
 *            entries must be encoded in the UTF7-IMAP charset. Example:
 *
 *              array('#shared/', '#news/', '#public/')
 *
 *            This parameter should only be used if you want to allow access
 *            to namespaces that may not be publicly advertised by the IMAP
 *            server (see RFC 2342 [3]). These additional namespaces will be
 *            ADDED to the list of available namespaces returned by the
 *            server. (IMAP only)
 *
 * preferred: (string | array) Useful if you want to use the same backends.php
 *            file for different machines. If the hostname of the IMP machine
 *            is identical to one of those in the preferred list, then that
 *            entry will be selected by default on the login screen. Otherwise
 *            the first entry in the list is selected.
 *
 * thread: (string) Set the preferred thread sort algorithm. This algorithm
 *         must be supported by the remote server. By default, IMP attempts
 *         to use REFERENCES sorting and, if this is not available, it will
 *         fall back to ORDEREDSUBJECT sorting done on the local server.
 *
 * timeout: (integer) Set the server timeout (in seconds).
 */

/* Any entries whose key value ('foo' in $servers['foo']) begin with '_'
 * (an underscore character) will be treated as prompts, and you won't be
 * able to log in to them. The only property these entries need is 'name'.
 * This lets you put labels in the list, like this example: */
$servers['_prompt'] = array(
    'name' => _("Choose a mail server:")
);

/* Example configurations: */

$servers['imap'] = array(
    // ENABLED by default
    //'disabled' => true,
    'name' => 'IMAP Server',
    'hostspec' => 'localhost',
    'hordeauth' => false,
    'protocol' => 'imap',
    'port' => 143,
    'secure' => false,
    'maildomain' => '',
    // 'smtphost' => 'smtp.example.com',
    // 'smtpport' => 25,
    'cache' => false,
);

$servers['advanced'] = array(
    // Disabled by default
    'disabled' => true,
    'name' => 'Advanced IMAP Server',
    'hostspec' => 'localhost',
    'hordeauth' => false,
    'protocol' => 'imap',
    'port' => 143,
    'secure' => false,
    'maildomain' => '',
    // 'smtphost' => 'smtp.example.com',
    // 'smtpport' => 25,
    // 'admin' => array(
    //     'params' => array(
    //         'admin_user' => 'cyrus',
    //         'admin_password' => 'cyrus_pass',
    //         'userhierarchy' => 'user.'
    //     ),
    // ),
    'quota' => array(
        'driver' => 'imap',
        'params' => array(
            'hide_when_unlimited' => true,
            'unit' => 'MB'
        )
    ),
    'acl' => true,
    'cache' => false,
);

$servers['pop'] = array(
    // Disabled by default
    'disabled' => true,
    'name' => 'POP3 Server',
    'hostspec' => 'localhost',
    'hordeauth' => false,
    'protocol' => 'pop3',
    'port' => 110,
    'secure' => false,
    'maildomain' => '',
    // 'smtphost' => 'smtp.example.com',
    // 'smtpport' => 25,
    'cache' => false,
);

$servers['secure-imap'] = array(
    // Disabled by default
    'disabled' => true,
    'name' => 'Secure IMAP Server',
    'hostspec' => 'localhost',
    'hordeauth' => false,
    'protocol' => 'imap',
    'port' => 143,
    'secure' => 'tls',
    'maildomain' => '',
    // 'smtphost' => 'smtp.example.com',
    // 'smtpport' => 25,
    'acl' => false,
    'cache' => false,
);

if ($GLOBALS['conf']['kolab']['enabled']) {
    $servers['kolab'] = array(
        // Disabled by default
        'disabled' => true,
        'name' => 'Kolab Cyrus IMAP Server',
        'hostspec' => 'localhost',
        'hordeauth' => 'full',
        'protocol' => 'imap',
        'port' => $GLOBALS['conf']['kolab']['imap']['port'],
        'secure' => false,
        'maildomain' => $GLOBALS['conf']['kolab']['imap']['maildomain'],
        'quota' => array(
            'driver' => 'imap',
            'params' => array(
                'hide_quota_when_unlimited' => true,
                'unit' => 'MB'
            )
        ),
        'acl' => true,
        'cache' => false,
    );
}

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/backends.local.php')) {
    include dirname(__FILE__) . '/backends.local.php';
}
