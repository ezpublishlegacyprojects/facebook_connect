<?php
//
// Created on: <25-Aug-2009 00:00:00 ar>
//
// SOFTWARE NAME: eZ Facebook Connect
// SOFTWARE RELEASE: 1.0
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


$Module = $Params['Module'];

if ( !class_exists( 'Facebook' ) )
{
    include_once('extension/facebook_connect/lib/facebook-platform/php/facebook.php');
}

$ini         = eZINI::instance();
$contentINI  = eZINI::instance( 'content.ini' );
$apiKey      = $contentINI->variable( 'FacebookConnect', 'APIKey' );
$secret      = $contentINI->variable( 'FacebookConnect', 'Secret' );
$errMsg      = '';
$fbException = '';

$fb = new Facebook( $apiKey, $secret );

// Require that facebook user is loggedin, user will be redirected to facebook login page if his not logged in
$fbUid = $fb->require_login();

// Make sure session is available for javascript code as well
try
{
    $fb->promote_session();
}
catch (FacebookRestClientException $e)
{
    // If this happens force login, but do it only once (or else you'll end up in a login loop)
    if ( !isset( $_GET['auth_token'] ) )
        $fb->redirect( $fb->get_login_url(Facebook::current_url(), $fb->in_frame()));
    else
        $fbException = $e->getMessage();   
}

// Figgure out redirect url
$http           = eZHTTPTool::instance();
$redirectionURI = $http->postVariable( 'RedirectURI', '' );
if ( $redirectionURI === '' || trim( $redirectionURI ) === '' )
{
    // Only use LastAccessesURI session value if RequireUserLogin is disabled
    if ( $ini->variable( 'SiteAccessSettings', 'RequireUserLogin' ) !== 'true'  )
    {
        if ( $http->hasSessionVariable( 'LastAccessesURI' ) )
            $redirectionURI = $http->sessionVariable( 'LastAccessesURI' );
    }

    if ( $http->hasSessionVariable( 'RedirectAfterLogin' ) )
    {
        $redirectionURI = $http->sessionVariable( 'RedirectAfterLogin' );
    }

    if ( $redirectionURI === '' || $redirectionURI === '/' || strpos( $redirectionURI, 'facebook/connect' ) !== false )
    {
        $redirectionURI = $ini->variable( 'SiteSettings', 'DefaultPage' );
    }
}

$user = FaceBookConnectUser::fetchByFacebookID( $fbUid );
if ( $user instanceof eZUser )
{
    if ( $user->isEnabled() )
    {
        // Login existing facebook user
        $user->loginCurrent();
        //FaceBookConnectUser::fbAddPlacment( $user->id() );// @TODO: Add setting to controll if user is valdated to be in fb user group on login
        return $Module->redirectTo( $redirectionURI );
    }
    $errMsg = ezi18n( 'design/standard/facebook', 'You allready have a facebook user "%1" on this site, but the user is disabled.', null, array( $user->attribute('login') ) );
    $errMsg = $errMsg . '<br />' . ezi18n( 'design/standard/facebook', 'If you have just registered, make sure you activate your user with the mail you recived!' );
}
else if ( $Module->isCurrentAction( 'Login' ) &&
     $Module->hasActionParameter( 'UserLogin' ) &&
     $Module->hasActionParameter( 'UserPassword' ) )
{
    $userLogin = $Module->actionParameter( 'UserLogin' );
    $userPassword = $Module->actionParameter( 'UserPassword' );
    
    if ( $userLogin && $userPassword )
    {
        $user = eZUser::loginUser( $userLogin, $userPassword );
        if ( $user instanceof eZUser )
        {
            $hasAccessToSite = $user->canLoginToSiteAccess( $GLOBALS['eZCurrentAccess'] );
            if ( !$hasAccessToSite )
            {
                $user->logoutCurrent();
            }
            else
            {
                FaceBookConnectUser::fbConnectUser( $user->id(), $fbUid );
                FaceBookConnectUser::fbAddPlacment( $user->id() );
                return $Module->redirectTo( $redirectionURI );
            }
        }
    }
    $errMsg = ezi18n( 'design/standard/facebook', 'Could not login user "%1", correct password?', null, array( $userLogin ) );
}
else if ( $Module->isCurrentAction( 'Register' ) )
{
    if ( $http->hasSessionVariable( 'RegisterUserID' ) )
    {
        $registerUserId = $http->sessionVariable( 'RegisterUserID' );
        $user = FaceBookConnectUser::fetchByFacebookID( $registerUserId );
        if ( $user instanceof eZUser )
        {
            return $Module->redirectTo( 'user/register' );
        }
        // cleanup
        $object = eZContentObject::fetch( $registerUserId );
        if ( $object instanceof eZContentObject )
            $object->currentVersion()->removeVersions();

        $http->removeSessionVariable( 'RegisterUserID' );
    }

    if ( eZSession::userHasSessionCookie() && eZSession::userSessionIsValid() )
    {
        $defaultUserPlacement = (int) ( $contentINI->hasVariable( 'FacebookConnect', 'DefaultUserPlacement' ) ? $contentINI->variable( 'FacebookConnect', 'DefaultUserPlacement' ) : $ini->variable( 'UserSettings', 'DefaultUserPlacement' ) );

        $db   = eZDB::instance();
        $rows = $db->arrayQuery( "SELECT count(node_id) as count FROM ezcontentobject_tree WHERE node_id = $defaultUserPlacement" );
        if ( $rows[0]['count'] < 1 )
        {
            $errMsg = ezi18n( 'design/standard/user', 'The node (%1) specified in [UserSettings].DefaultUserPlacement setting in site.ini does not exist!', null, array( $defaultUserPlacement ) );
            eZDebug::writeError( "$errMsg" );
        }
        else
        {
            $userClassID      = (int) ( $contentINI->hasVariable( 'FacebookConnect', 'UserClassID' ) ? $contentINI->variable( 'FacebookConnect', 'UserClassID' ) : $ini->variable( 'UserSettings', 'UserClassID' ) );
            $userCreatorID    = (int) ( $contentINI->hasVariable( 'FacebookConnect', 'UserCreatorID' ) ? $contentINI->variable( 'FacebookConnect', 'UserCreatorID' ) : $ini->variable( 'UserSettings', 'UserCreatorID' ) );
            $defaultSectionID = (int) ( $contentINI->hasVariable( 'FacebookConnect', 'DefaultSectionID' ) ? $contentINI->variable( 'FacebookConnect', 'DefaultSectionID' ) : $ini->variable( 'UserSettings', 'DefaultSectionID' ) );

            $class = eZContentClass::fetch( $userClassID );
            if ( $class instanceof eZContentClass )
            {
                // Create object by user 14 ($userCreatorID) in section 1 ($defaultSectionID)
                $userContentObject = $class->instantiate( $userCreatorID, $defaultSectionID );
                $userId = $userContentObject->attribute( 'id' );

                // Store the ID in session variable (as used by user/register)
                $http->setSessionVariable( 'RegisterUserID', $userId );

                // Connect new user to facebook id
                // @TODO: Either this should be done after user/register, or there needs a script to clean invalid entryes
                // alternative: implement as datatype, so this is removed when user is deleted (either by admin or by draft cleanup scripts)
                FaceBookConnectUser::fbConnectUser( $userId, $fbUid );

                // Assign node to newly created user
                $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $userId,
                                                                   'contentobject_version' => 1,
                                                                   'parent_node' => $defaultUserPlacement,
                                                                   'is_main' => 1 ) );
                $nodeAssignment->store();

                // Redirect to user/register which will show register form for newly created user
                return $Module->redirectTo( 'user/register' );
            }
            else
            {
                $errMsg = ezi18n( 'design/standard/facebook', 'Could not fetch class by class id %1!', null, array( $userClassID ) );
            }
        }
    }
    else
    {
        $errMsg = ezi18n( 'design/standard/facebook', 'Session validation failed: You need to enable cookies in your browser to register on this site!' );
    }
}


$currentUser = eZUser::currentUser();
if ( $Module->isCurrentAction( 'ConnectCurrentUser' ) && $currentUser->isLoggedIn() )
{
    FaceBookConnectUser::facebookConnectUser( $currentUser->id(), $fbUid );
    FaceBookConnectUser::fbAddPlacment( $currentUser->id() );
    return $Module->redirectTo( $redirectionURI );
}


include_once( 'kernel/common/template.php' );
$tpl = templateInit();
$tpl->setVariable( 'redirect_uri', $redirectionURI );
$tpl->setVariable( 'errMsg', $errMsg );
$tpl->setVariable( 'fb_uid', $fbUid );
$tpl->setVariable( 'fb_apikey', $apiKey );
$tpl->setVariable( 'fb_exception', $fbException );
$tpl->setVariable( 'current_user', $currentUser );
$tpl->setVariable( 'get_params', ( isset( $_GET['auth_token'] ) ?  '?auth_token=' . $_GET['auth_token'] : '' ) );


$Result = array();
$Result['content'] = $tpl->fetch( 'design:facebook/connect.tpl' );
$Result['path'] = array( array( 'url' => '',
                                'text' => 'Facebook'),
                          array( 'url' => '',
                                'text' => 'Connect' ) );





?>