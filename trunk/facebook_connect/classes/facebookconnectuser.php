<?php
//
// Definition of fbcObject class
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

class FaceBookConnectUser extends eZUser
{
     /**
     * Construct
     * 
     * @param array $row
     */
    protected function __construct( $row )
    {
        if ( isset( $row['fb_uid'] ) )
        {
            $this->FacebookUserId = $row['fb_uid'];
        }
        $this->eZUser( $row );
    }

    /** definition of FaceBookConnectUser, extends eZUser definition
     * 
     *  @return array
     */
    static function definition()
    {
        static $def = null;
        if ( $def === null )
        {
            $def = parent::definition();
            $def['class_name'] = 'FaceBookConnectUser';
            $def['function_attributes']['fb_uid'] = 'getFacebookID';
        }
        return $def;
    }

    /** Internal facebook user id (null if none)
     * 
     * @var float|null $FacebookUserId
     */
    protected $FacebookUserId = null;

    /** Get facebook uid
     * 
     * @return float|false Returns false if user does not have facebook user id
     */
    public function getFacebookID()
    {
        if ( $this->FacebookUserId === null )
        {
            $db  = eZDB::instance();
            $ret = $db->arrayQuery( 'SELECT facebookconnectuser.*
                FROM facebookconnectuser
                WHERE facebookconnectuser.user_id=' . $this->id() );
            unset($db);
    
            if ( isset( $ret[0]['fb_uid'] ) )
            {
                $this->FacebookUserId = $ret[0]['fb_uid'];
            }
            else
            {
                if ( $ret === false )
                    eZDebug::writeError( 'The facebookconnectuser table seems to be missing, contact your administrator', __METHOD__ );
                $this->FacebookUserId = false;
            }
        }
        return $this->FacebookUserId;
    }

    /**
     * Create FaceBookConnectUser by eZUser object
     * 
     * @param eZUser $user
     * @return FaceBookConnectUser
     */
    static public function createByeZUser( eZUser $user )
    {
        $fbUser = new self( array() );
        foreach ( $user->attributes() as $attribute )
        {
            $fbUser->setAttribute( $attribute, $user->attribute( $attribute ) );
        }
        return $fbUser;
    }

    /**
     * Fetch user by facebook id!
     * 
     * @param int $fb_uid
     * @return null|FaceBookConnectUser
     */
    static function fetchByFacebookID( $fb_uid )
    {
        if ( !isset( $fb_uid ) || !$fb_uid || !is_numeric( $fb_uid ) )
        {
            return null;
        }

        $db  = eZDB::instance();
        $sql = "SELECT ezuser.*, facebookconnectuser.fb_uid
                FROM ezuser, facebookconnectuser
                WHERE facebookconnectuser.fb_uid=$fb_uid
                  AND ezuser.contentobject_id=facebookconnectuser.user_id";
        $ret = $db->arrayQuery( $sql );
        unset($db);

        if ( isset( $ret[0] ) && is_array( $ret ) )
        {
            return new self( $ret[0] );
        }
        else if ( $ret === false )
        {
            eZDebug::writeError( 'The facebookconnectuser table seems to be missing,
                          contact your administrator', __METHOD__ );
        }
        return null;
    }

    /**
     * Connects this user to facebook user id.
     * Note: make sure you check that facebook id does not
     * exist already, using for instance {@link FaceBookConnectUser::fetchByFacebookID()}
     * or if you fetched user by eZ Publish user id {@link FaceBookConnectUser::getFacebookID()}
     * 
     * @param int $userID
     * @param int $fb_uid
     * @return bool Return true on success
     */
    static public function fbConnectUser( $userID, $fb_uid )
    {
        if ( $userID && is_numeric( $userID ) && $fb_uid && is_numeric( $fb_uid ) )
        {
            $db  = eZDB::instance();
            $ret = $db->query( "INSERT INTO facebookconnectuser ( fb_uid, user_id ) VALUES ( $fb_uid, $userID )" );
            unset($db);
            if ( $ret !== false )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * {@see FaceBookConnectUser::fbConnectUser()}
     * 
     * @param int $fb_uid
     * @return bool Return true on success
     */
    public function fbConnect( $fb_uid )
    {
        if ( !$this->FacebookUserId )
        {
            $ret = self::fbConnectUser( $this->id(), $fb_uid );
            if ( $ret !== false )
            {
                $this->FacebookUserId = $fb_uid;
                return true;
            }
        }
        return false;
    }

    /**
     * Adds eZ Publish facebook user group (content.ini[FacebookConnect]DefaultUserPlacement) 
     * node placment if defined.
     * NOTE: User (hence object) will only get new node placment if published!
     * 
     * @param int $userID
     * @return bool Return true on success
     */
    static public function fbAddPlacment( $userID )
    {
        $ini  = eZINI::instance( 'content.ini' );
        if ( $userID && is_numeric( $userID ) && $ini->hasVariable( 'FacebookConnect', 'DefaultUserPlacement' ) )
        {
            $nodeId = (int) $ini->variable( 'FacebookConnect', 'DefaultUserPlacement' );
            $assignedNodes = self::getParentNodeIdListByContentObjectID( $userID );
            if ( $nodeId && $assignedNodes && !in_array( $nodeId, $assignedNodes ) )
            {
                $object = eZContentObject::fetch( $userID );
                if ( $object instanceof eZContentObject )
                {
                    $mainNodeId   = $object->attribute( 'main_node_id' );
                    $insertedNode = $object->addLocation( $nodeId, true );
                    // Now set is as published and fix main_node_id
                    $insertedNode->setAttribute( 'contentobject_is_published', 1 );
                    $insertedNode->setAttribute( 'main_node_id', $mainNodeId );
                    $insertedNode->setAttribute( 'contentobject_version', $object->attribute( 'current_version' ) );
                    // Make sure the url alias is set updated.
                    $insertedNode->updateSubTreePath();
                    $insertedNode->sync();

                    eZSearch::addNodeAssignment( $mainNodeId, $userID, array( $nodeId ) );

                    eZUser::cleanupCache();

                    eZContentCacheManager::clearContentCacheIfNeeded( $userID );
                    return true;
                }
            }
        }
        return false;
    }

    static protected function getParentNodeIdListByContentObjectID( $objectId )
    {
        if ( method_exists('eZContentObjectTreeNode','getParentNodeIdListByContentObjectID') )
        {
            return eZContentObjectTreeNode::getParentNodeIdListByContentObjectID( $objectId );
        }

        if ( !$objectId )
            return null;

        $db = eZDB::instance();
        $query = 'SELECT parent_node_id FROM ezcontentobject_tree WHERE contentobject_id = ' . $objectId;
        $rows = $db->arrayQuery( $query );
        $parentNodeIDs = array();
        foreach( $rows as $row )
        {
            $parentNodeIDs[] = $row['parent_node_id'];
        }
        return $parentNodeIDs;
    }
    
}

?>