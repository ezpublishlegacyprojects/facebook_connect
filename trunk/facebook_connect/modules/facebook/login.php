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

//echo "Your facebook uid: $fbUid <br />" . $fbException;

$currentUser = eZUser::currentUser();

if ( $contentINI->hasVariable( 'FacebookConnect', 'AnonymousFacebookUserId' )
  && $contentINI->variable( 'FacebookConnect', 'AnonymousFacebookUserId' ) )
{
    $eZPublishFacebookUserId = $contentINI->variable( 'FacebookConnect', 'AnonymousFacebookUserId' );
}
else
{
    eZDebug::writeWarning('content.ini[FacebookConnect]AnonymousFacebookUserId is not set, this is recomended for this view to know that facebook a user is logged in!', 'facebook/login');
    $eZPublishFacebookUserId = $ini->variable( 'UserSettings', 'AnonymousUserID' );
}

// Login as the eZ Publish facebook user so eZ Publish knows his logged in
if ( $currentUser->attribute('contentobject_id') != $eZPublishFacebookUserId )
{
    $eZPublishFacebookUser = eZUser::instance( $eZPublishFacebookUserId );
    if ( $eZPublishFacebookUser instanceof eZUser && $eZPublishFacebookUser->isEnabled() )
    {
        $eZPublishFacebookUser->loginCurrent();
    }
    else
    {
        eZDebug::writeError('Could not fetch/login eZ Publish Facebook User with id: ' . $eZPublishFacebookUserId, 'facebook/login');
    }
}

// Figgure out redirect url
$http            = eZHTTPTool::instance();
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

    if ( $redirectionURI === '' || $redirectionURI === '/' )
    {
        $redirectionURI = $ini->variable( 'SiteSettings', 'DefaultPage' );
    }
}

return $Module->redirectTo( $redirectionURI );

?>