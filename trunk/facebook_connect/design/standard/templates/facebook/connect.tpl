<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="user-login">

<form method="post" action={concat("/facebook/connect/", $get_params)|ezurl} name="fbloginform">

    <div class="attribute-header">
        <h2 class="long">{"Facebook Connect"|i18n("design/facebook/connect")}</h2>
    </div>

    {if $errMsg}
	    <div class="message-error">
	    <h2>{"Error"|i18n("design/ezwebin/user/login")}</h2>
	    <ul>
	        <li>{$errMsg}</li>
	    </ul>
	    </div>
    {/if}

<div class="block">
<div class="left">
	<div class="attribute-header">
	    <h2 class="long">{"Login"|i18n("design/ezwebin/user/login")}</h2>
	</div>
	{if $current_user.is_logged_in}
	    <div class="buttonblock">
	    <input class="defaultbutton" type="submit" name="ConnectCurrentUserButton" value="{'Connect'|i18n('design/ezwebin/user/login','Button')}" tabindex="1" />
	    </div>
	{else}
		<div class="block">
		<label for="id1">{"Username"|i18n("design/ezwebin/user/login",'User name')}</label><div class="labelbreak"></div>
		<input class="halfbox" type="text" size="10" name="Login" id="id1" value="{$User:login|wash}" tabindex="1" />
		</div>

		<div class="block">
		<label for="id2">{"Password"|i18n("design/ezwebin/user/login")}</label><div class="labelbreak"></div>
		<input class="halfbox" type="password" size="10" name="Password" id="id2" value="" tabindex="2" />
		</div>

		<div class="buttonblock">
		<input class="defaultbutton" type="submit" name="LoginButton" value="{'Login'|i18n('design/ezwebin/user/login','Button')}" tabindex="3" />
		</div>
	{/if}
</div>
<div class="right">
    <div class="attribute-header">
        <h2 class="long">{"Register user"|i18n("design/ezwebin/user/register")}</h2>
    </div>

    <div class="buttonblock">
    {if ezmodule( 'user/register' )}
        <input class="button" type="submit" name="RegisterButton" id="RegisterButton" value="{'Sign up'|i18n('design/ezwebin/user/login','Button')}" tabindex="4" />
    {/if}
    </div>
</div>
</div>


{if ezini( 'SiteSettings', 'LoginPage' )|eq( 'custom' )}
    <p><a href={'/user/forgotpassword'|ezurl}>{'Forgot your password?'|i18n( 'design/ezwebin/user/login' )}</a></p>
{/if}

<input type="hidden" name="RedirectURI" value="{$User:redirect_uri|wash}" />

</form>

</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>