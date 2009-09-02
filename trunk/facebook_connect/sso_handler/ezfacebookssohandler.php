<?php
//
// Definition of eZFacebookSSOHandler class
//
// Created on: <25-Aug-2009 12:42:08 ar>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Facebook Connect
// SOFTWARE RELEASE: 1.x
// COPYRIGHT NOTICE: Copyright (C) 2009 eZ Systems AS
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

/**
 * SSO handler used by Facebook Connect extension
 */
class eZFacebookSSOHandler
{
    const CHECK_TIME = 'eZFacebookSSOHandler_checkTime';

    function handleSSOLogin()
    {
        $checkUser = true;
        $http      = eZHTTPTool::instance();
        $uri       = eZURI::instance();
        
        if ( strpos( $uri->uriString(), 'user/logout' ) !== false )
        {
            $checkUser = false;
        }
        // Only check every 60 seconds to not connect to much to facebook
        else if ( $http->hasSessionVariable( self::CHECK_TIME ) )
        {
            $checkUser = time() - $http->sessionVariable( self::CHECK_TIME ) >= 60;
        }

        if ( $checkUser )
        {
            // include Facebook PHP API
            if ( !class_exists( 'Facebook' ) )
            {
                include_once('extension/facebook_connect/lib/facebook-platform/php/facebook.php');
            }
            
            $contentINI  = eZINI::instance( 'content.ini' );
            $apiKey      = $contentINI->variable( 'FacebookConnect', 'APIKey' );
            $secret      = $contentINI->variable( 'FacebookConnect', 'Secret' );

            // Make sure we don't check this again for this annonymus session
            $http->setSessionVariable( self::CHECK_TIME, time() );

            // Instantiate Facebook class
            $fb = new Facebook( $apiKey, $secret );
            $fbUid = $fb->get_loggedin_user();

            // Check if Facebook User is logged in
            if ( $fbUid > 0 )
            {
                $eZPublishFacebookUserId = false;
                if ( $contentINI->hasVariable( 'FacebookConnect', 'AnonymousFacebookUserId' )
                  && $contentINI->variable( 'FacebookConnect', 'AnonymousFacebookUserId' ) )
                {
                    // if this setting is set, then use it for shared facebook user
                    $eZPublishFacebookUserId = $contentINI->variable( 'FacebookConnect', 'AnonymousFacebookUserId' );
                    $user = eZUser::fetch( $eZPublishFacebookUserId );
                }
                else
                {
                    // If not, try to find connect user
                    $user = FaceBookConnectUser::fetchByFacebookID( $fbUid );
                }

                if ( $user instanceof eZUser )
                {
                    // Check if user is enabled withouth using $user->isEnabled() since it compares $this == eZUser::current()
                    $setting = eZUserSetting::fetch( $user->id() );
                    if ( $setting instanceof eZUserSetting && $setting->attribute( 'is_enabled' ) )
                    {
                        return $user;
                    }
                    eZDebug::writeDebug( "User was logged in to Facebook, but was not enabled.", __METHOD__ );
                }
                else
                {
                    if ( $eZPublishFacebookUserId )
                        eZDebug::writeDebug( "User was logged in to Facebook, but could not fetch eZ Publish user by id $eZPublishFacebookUserId.", __METHOD__ );
                    else
                        eZDebug::writeDebug( "User was logged in to Facebook, but could not find a eZ Publish Facebook connect user.", __METHOD__ );
                }
            }
            else
            {
                eZDebug::writeDebug( 'User was not logged in to Facebook.', __METHOD__ );
            }
        }
        return false;
    }
}

?>