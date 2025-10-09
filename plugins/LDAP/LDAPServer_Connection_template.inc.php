<?php

$_ldap= load_pluginModule("LDAP");
require_once( $_ldap['mindtake_ldap'] );

/**
 * main properties to connect over LDAP/Server
 * @author Alexander Kolli
 */
class LDAPServer_Connection extends LDAPServer {

        /*public:*/
        var $protocol_ = "ldap"; // "ladps" for SSL/TSL connection
        var $host_ = 'example.com'; // host of LDAP server
        var $port_ = '389'; // standard port, for LDAPs mostly '636';

        var $loginAttribName_ = 'cn';
        var $loginUserName_ = '<user name>'; // user name for standard user first login
        var $password_ = '<password>'; // // password  for standard user first login
        var $loginBaseDN_ = 'ou=FunctionUser,dc=example,dc=com'; // using for standard user
        var $baseDN_ = 'dc=example,dc=com'; // using for ldap_search
        
        /**
         * function to create filter for searching user attributes over ldap/server.
         * Using also membervariable $baseDN_ for searching (ldap_search)
         * 
         * @param string $user name of user want to login
         * @return string created filter for search
         */
        public function filterForSearchAttributes(string $user) : string
        {
            $filter= "(&(user=".$user.")(objectClass=user))";
            return $filter;
        }
        
        /**
         * attributes can get back
         * filled from LDAP-server
         */
        var $retrieveAttributes_= array('user',
                                        'name',
                                        'description',
                                        'memberOf',
                                        'mail'				   );

        /**
         * function to create the base DN for password verification using the ldap_bind function
         * 
         * @param string $user name of user want to login
         * @param array $attributes all attributes found over ldap for user
         * @return string created base DN string
         */
        public function userBaseDnCreation(string $user, array $attributes) : string
        {
            $baseDN= $this->loginAttribName_."=$user,";
            $baseDN.= "ou=users,"; // or add some attributes specific from user
            $baseDN.= $this->baseDN_;
            return $baseDN;
        }
        
        /**
         * array of options.<br />
         * only the option key names 'debug_level'
         * have to be correct spelling,
         * all other KEYs only be for debug output.<br />
         * inner ldap options see: https://www.php.net/manual/en/function.ldap-get-option.php
         * @var array $LDAP_options
         */
        var $LDAP_options= array(
            'debug_level'                           => array( LDAP_OPT_DEBUG_LEVEL, 7), // debug level, get to run only py php cli
                                                                                        // try $> php -f ldap_check_commandline.php
            'LDAP_OPT_TIMELIMIT'                    => array( LDAP_OPT_TIMELIMIT, 10 /*sec*/)
        );
        /**
         * array of TLS options.<br />
         * only follow option key names are necessary to writing correctly:
         * 'require_tls'    - whether need an SSL/TLS connection
         * 'ldap_start_tls' - whether connection need php identification <code>ldap_start_tls()</code>
         * all other KEYs only be for debug output.<br />
         * inner ldap options see: https://www.php.net/manual/en/function.ldap-get-option.php
         * @var array $LDAP_TLS_options
         */
        var $LDAP_TLS_options= array(
            'require_tls' => false, // for SSL/TLS connection should be true
            'LDAP_OPT_PROTOCOL_VERSION'     => array( LDAP_OPT_PROTOCOL_VERSION, 3),
        //    'LDAP_OPT_SSL'                  => array( LDAP_OPT_SSL, true),
            'LDAP_OPT_X_TLS_REQUIRE_CERT'   => array( LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER), //1),
            'LDAP_OPT_X_TLS_CACERTFILE'     => array( LDAP_OPT_X_TLS_CACERTFILE, '/<path to ca file or bundle>/tls-ca-bundle.pem'),
            'LDAP_OPT_X_TLS_CERTFILE'       => array( LDAP_OPT_X_TLS_CERTFILE, '/<path to ca file or bundle>/public_certificate.crt'),
            'LDAP_OPT_X_TLS_KEYFILE'        => array( LDAP_OPT_X_TLS_KEYFILE, '/<path to ca file or bundle>/private.key'),
            'LDAP_OPT_REFERRALS'            => array( LDAP_OPT_REFERRALS, 0)

        // for more different existing domains on same host
        // also different certificates possible
        // as example:
    //      'LDAP_OPT_X_TLS_CERTFILE'       => array( LDAP_OPT_X_TLS_CERTFILE, array( "example1.com" => '/<path to first ca file or bundle>/public_certificate1.crt',
    //                                                                                "example2.com" => '/<path to second ca file or bundle>/public_certificate2.crt' ) )

        );
}

?>