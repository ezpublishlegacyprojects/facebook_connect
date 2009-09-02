<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Publish Website Interface
// SOFTWARE RELEASE: 1.4-2
// COPYRIGHT NOTICE: Copyright (C) 1999-2009 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

$Module = array( 'name' => 'Facebook Connect Module' );

$ViewList = array();

// Login shared eZ Publish user using facebook login
$ViewList['login'] = array( 'functions' => array( 'login' ),
                            'script' => 'login.php',
                            'params' => array()
);

// Connect a eZ Publish user with a facebook user (using either login or register)
$ViewList['connect'] = array( 'functions' => array( 'connect' ),
                           'script' => 'connect.php',
                           'single_post_actions' => array( 'LoginButton' => 'Login',
                                                           'ConnectCurrentUserButton' => 'ConnectCurrentUser',
                                                           'RegisterButton' => 'Register' ),
                           'post_action_parameters' => array( 'Login' => array( 'UserLogin' => 'Login',
                                                                                'UserPassword' => 'Password',
                                                                                'UserRedirectURI' => 'RedirectURI' ) ),
                           'params' => array()
);


$FunctionList = array();
$FunctionList['login'] = array( );
$FunctionList['connect'] = array( );



?>
