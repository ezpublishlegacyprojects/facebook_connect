<?php
//
// Definition of FaceBookConnectType class
//
// SOFTWARE NAME: Facebook Connect
// SOFTWARE RELEASE: 1.0
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

/**
 * File containing the FaceBookConnectType class.
 *
 * @package eZDatatype
 */

/**
 * Class providing the fbConnect datatype.
 *
 * @package eZDatatype
 * @see FaceBookConnectType
 */

class FaceBookConnectType extends eZDataType
{
    const DATA_TYPE_STRING = 'facebookconnect';

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct( self::DATA_TYPE_STRING, ezi18n( 'extension/fbconnect/datatype', "Facebook User Connect", 'Datatype name' ) );
    }

    /**
     * Validate post data, these are then used by
     * {@link self::fetchObjectAttributeHTTPInput()}
     * 
     * @param eZHTTPTool $http
     * @param string $base
     * @param eZContentObjectAttribute $contentObjectAttribute
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    /**
     * Set parameters from post data, expects post data to be validated by
     * {@link self::validateObjectAttributeHTTPInput()}
     * 
     * @param eZHTTPTool $http
     * @param string $base
     * @param eZContentObjectAttribute $contentObjectAttribute
     */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        return true;
    }

    /**
     * Stores the content, as set by {@link self::fetchObjectAttributeHTTPInput()}
     * or {@link self::initializeObjectAttribute()}
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return bool
     */
    function storeObjectAttribute( $contentObjectAttribute )
    {   
        return true;
    }

    /**
     * Init attribute ( also handles version to version copy, and attribute to attribute copy )
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param int|null $currentVersion
     * @param eZContentObjectAttribute $originalContentObjectAttribute
     */
    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
    }

    /**
     * Return content hash
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return array
     */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $data = array( 'user' => null, 'fbuid' => null );
        $userID = $contentObjectAttribute->attribute( 'contentobject_id' );
        if ( !empty( $GLOBALS["eZUserGlobalInstance_$userID"] ) )
            $user = $GLOBALS["eZUserGlobalInstance_$userID"]; 
        else
            $user = eZUser::fetch( $userID ); 

        if ( $user instanceof eZUser )
        {
            $fbuser = FaceBookConnectUser::createByeZUser( $user );
            $fb_uid = $fbuser->getFacebookID();
            $data['user'] = $fbuser;
            if ( $fb_uid )
                $data['fbuid'] = $fb_uid;
        }
        return $data;
    }

    /**
     * Indicates if attribute has content or not
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return bool
     */
    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $userID = $contentObjectAttribute->attribute( 'contentobject_id' );
        if ( !empty( $GLOBALS["eZUserGlobalInstance_$userID"] ) )
            $user = $GLOBALS["eZUserGlobalInstance_$userID"]; 
        else
            $user = eZUser::fetch( $userID );

        return $user instanceof eZUser;
    }

    /**
     * Generate meta data of attribute
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return string
     */
    function metaData( $contentObjectAttribute )
    {
        $data = $contentObjectAttribute->attribute( 'content' );
        return $data['fbuid'];
    }

    /**
     * Indicates that datatype is searchable {@link self::metaData()}
     * 
     * @return bool
     */
    function isIndexable()
    {
        return true;
    }

    /**
     * Return string data for cosumption by {@link self::fromString()}
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return string
     */
    function toString( $contentObjectAttribute )
    {
        $data = $contentObjectAttribute->attribute( 'content' );
        return $data['fbuid'] ? $data['fbuid'] : '';
    }

    /**
     * Store data from string format as created in  {@link self::toString()}
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param string $string
     */
    function fromString( $contentObjectAttribute, $string )
    {
    	if ( $string !== '' && is_numeric( $string ) )
    	{
            if ( FaceBookConnectUser::fetchByFacebookID( $string ) )
            {
                eZDebug::writeWarning( "Facebook user $string already exists, can not create connection!", __MEHOD__ );
            }
            else
            {
                FaceBookConnectUser::fbConnectUser( $contentObjectAttribute->attribute( 'contentobject_id' ), $string );
            }
    	}
    }

    /**
     * Generate title of attribute
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param string|null $name
     * @return string
     */
    function title( $contentObjectAttribute, $name = null )
    {
        $data = $contentObjectAttribute->attribute( 'content' );
        return $data['fbuid'];
    }

    /**
     * Delete Facebook user connection if there is one
     * 
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param int|null $version (Optional, deletes all versions if null)
     */
    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
    	if ( $version === null )
    	{
            FaceBookConnectUser::fbUnConnectUser( $contentObjectAttribute->attribute( 'contentobject_id' ) );
    	}
    }
}

eZDataType::register( FaceBookConnectType::DATA_TYPE_STRING, 'FaceBookConnectType' );

?>
