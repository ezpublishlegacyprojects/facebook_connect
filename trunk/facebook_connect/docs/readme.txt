Facebook Connect extension for eZ publish
Version 1.x by André R / eZ Systems AS

Version 0.1 by Simon Schneeberger / YMC AG
******************************************


 0. Features
************
The Facebook Connect extension integrates Facebook Connect API (http://wiki.developers.facebook.com/index.php/Client_Libraries)
into eZ publish. It allows you to connect your website with Facebook.


 1. Setup
*********
There are three ways to setup this extension:
A. Only client side integration, eZ Publish will not know if the user is facebook or anonymous user.
B. Hybrid, eZ Publish uses a shared user to be able to know that the user is Facebook user, but not witch one.
C. Integrated, there is one eZ Publish user pr Facebook user

1.0 Common tasks for all is:
----------------------------
1. Build a Facebook Application http://www.facebook.com/developers/createapp.php. Complete your API Key and Secret in content.ini.append
2. Add the APIkey and secret to your settings/override or settings/siteaceess/<siteaccess> content.ini.append.php file, like:
  [FacebookConnect]
  APIKey=<Your API Key>
  Secret=<Your Secret>


1.A. Client side only
---------------------
1. (optional) To make FBML syntax workable add xmlns:fb="http://www.facebook.com/2008/fbml" in your <head> tag in your pagelayout.tpl
2. Add the following line in the <body> tag.
   <script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
   Or using a locale (http://wiki.developers.facebook.com/index.php/Facebook_Locales):
   <script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/nb_NO" type="text/javascript"></script>
3. Add a rewrite rule for the static xd_receiver.html, like this:
        Rewriterule ^/extension/facebook_connect/xd_receiver.html - [L]
4. Add some Javascript to initialize Facebook code:
  <script type="text/javascript">
  {def $apikey = ezini('FacebookConnect', 'APIKey', 'content.ini')}
  FB.init("{$apikey}", "{'extension/facebook_connect/xd_receiver.html'|ezroot(no)}");
  </script>
5a. Create the login button with <fb:login-button></fb:login-button>
5b. Alternative button if you didn't enable FBML (#1):
    <a href="#" onclick="FB.Connect.requireSession(); return false;" >
    <img id="fb_login_image" src="http://static.ak.fbcdn.net/images/fbconnect/login-buttons/connect_light_medium_long.gif" alt="Connect" />
    </a>
6. Logout example:
    <a id="fb_logout_btn" href="JavaScipt:void(0);" onclick='FB.Connect.logoutAndRedirect( {'/'|ezurl} ); return false;'>Logut</a>


1.B. Hybrid  (facebook/login)
-----------------------------
1. Create a new user, where you place it depends on how you would like to threat him compared to anonymous user and other members.
2. Add object/user id to your settings/override or settings/siteaceess/<siteaccess> content.ini.append.php file, like:
  [FacebookConnect]
  AnonymousFacebookUserId=<eZPublish-Shared-Facebook-UserID>
3. Login button:
  <a href={"facebook/login"|ezurl}>
  <img src="http://static.ak.fbcdn.net/images/fbconnect/login-buttons/connect_light_medium_long.gif" alt="Connect"/>
  </a>
4. Give anonymous users access to facebook/login either in roles or your site.ini.append.php like:
  [RoleSettings]
  PolicyOmitList[]=facebook/login
5. (optional) Also follow the "1.A. Client side" tasks if you plan to use FBML or the FB javascript api.


1.C. Full integration (facebook/connect)
----------------------------------------
1. (optional) See facebook_connect/settings/content.ini for facebook/connect settings you can define
   so that for instance new users are created in another user group then general eZ Publish users.
2. Login button:
  <a href={"facebook/connect"|ezurl}>
  <img src="http://static.ak.fbcdn.net/images/fbconnect/login-buttons/connect_light_medium_long.gif" alt="Connect"/>
  </a>
3. Give anonymous users access to facebook/connect either in roles or your site.ini.append.php like:
  [RoleSettings]
  PolicyOmitList[]=facebook/connect
4. (optional) Also follow the "1.A. Client side" tasks if you plan to use FBML or the FB javascript api.



Logout button/link example for 1.B and 1.C:

  <script type="text/javascript">
  {def $apikey = ezini('FacebookConnect', 'APIKey', 'content.ini')}
  FB.init( "{$apikey}",
    "{'extension/ezfacebook_connect/xd_receiver.html'|ezroot(no)}",{literal}
    { ifUserConnected: function( uid ){
            var fbLogoutBtn = document.getElementById('fb_logout_btn');
            if ( fbLogoutBtn ){
                var fbLogoutHref = fbLogoutBtn.href;
                fbLogoutBtn.href = "JavaScript:void(0);"
                fbLogoutBtn.onclick = function(){ FB.Connect.logoutAndRedirect( fbLogoutHref ); return false; };
            }
    }});{/literal}
  </script>

  <a id="fb_logout_btn" href={"user/logout"|ezurl}>Logut</a>




For further informations on Facebook Connect platform have a look at:
  http://wiki.developers.facebook.com/index.php/Trying_Out_Facebook_Connect
  http://www.devtacular.com/articles/bkonrad/how-to-integrate-with-facebook-connect/


 2. SSO
*******
Included is a SSO login handler that can be used to automatically login users when they return
to your site if they are logged in to facebook.

The following lines have to be appended at the end of the site.ini.append.php
in either your override or siteaccess settings to enable it:

[UserSettings]
SingleSignOnHandlerArray[]=Facebook
ExtensionDirectory[]=facebook_connect


 3. Some Code Examples
**********************

Get the username:

Javascript:
( see: http://wiki.developers.facebook.com/index.php/JS_API_N_FB )

<script type="text/javascript">
(function( api )
{
  // require user to login 
  FB.Connect.requireSession( function( exception )
  {
      var myQuery = 'SELECT name FROM user WHERE uid=' + api.get_session().uid;
      api.fql_query(myQuery, getFQLResponse);
  });
  
  function getFQLResponse( result, exeption )
  {
    alert( result[0]['name'] );
  }
}( FB.Facebook.apiClient ));
</script>


PHP:
( see: http://wiki.developers.facebook.com/index.php/UrPics_Code_Walkthrough )
<?
  // include_once('extension/facebook_connect/lib/facebook-platform/php/facebook.php');
  $contentINI = eZINI::instance( 'content.ini' );
  $apiKey = $contentINI->variable( 'FacebookConnect', 'APIKey' );
  $secret = $contentINI->variable( 'FacebookConnect', 'Secret' );

  $fb = new Facebook( $apiKey, $secret );
  if( $fb->get_loggedin_user() > 0 )
  {
      $user = $fb->user;
      $userData = $fb->api_client->users_getInfo($user, array('first_name','last_name'));
      echo utf8_decode($userData[0]['first_name']) . utf8_decode($userData[0]['last_name']);
  }
?>