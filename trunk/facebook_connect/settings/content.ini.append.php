<?php /* #?ini charset="utf-8"?

[DataTypeSettings]
ExtensionDirectories[]=facebook_connect
AvailableDataTypes[]=facebookconnect


# Copy and uncomment to content.ini in your override, sites siteaccess or site extension
[FacebookConnect]
## Facebook Connect settings
#APIKey=<Your API Key>
#Secret=<Your Secret>

## facebook/login settings
# Shared facebook user used by facebook/login
# Makes it possible to use a singel different
# user then the normal eZ Publish Anonymous user
#AnonymousFacebookUserId=14

## facebook/connect settings
## If these are not set, then defaults from site.ini [UserSettings] are used
# Where to store newly self registered users and new connect users (add placment)
#DefaultUserPlacement=12
# Which section to place newly self registered users
# (Using 0 means that the user will get the section ID
#  from its new location)
#DefaultSectionID=0
# Which class to use for Facebook users (will not effect exitsing users that just connect)
#UserClassID=4
# Which user is considered the creator (Normally admin)
#UserCreatorID=14


*/ ?>