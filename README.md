# os-partnership
Make or Break Partnerships in Opensimulator

This repository consists of the in-world LSL/OSSL script required to initiate partnership management as well as a PHP script to interrogate and modify partnership details on user account profile records. There are a number of simple libraries accompanying the PHP script, which I culled from my own set of libraries - why invent a new wheel when the old one still works?

**WARNING**: Requires backend access to the grid database!

I have named the in-world script with an `ossl` extension rather than an `lsl` extension, because it contains `OSSL` extension functions.

# Index
- [OSSL Functions](#ossl-functions)
- [General Process](#general-process)
- [Files Not Included](#files-not-included)
- [The Certificate](#the-certificate)
- [The Secret](#the-secret)
- [Setting Up](#setting-up)
- [Debugging](#debugging)
- [Using the Proxy Script](#using-the-proxy-script)
- [Using XEngine](#using-xengine)


## OSSL Functions
[Back to Top](#os-partnership)

The LSL/OSSL script requires use of the following ossl functions. Default permissions are shown:
```lsl2
osGetAvatarList			${OSSL|osslParcelOG}ESTATE_MANAGER,ESTATE_OWNER
osGetNotecard			${OSSL|osslParcelO}ESTATE_MANAGER,ESTATE_OWNER
osGetGridName			always allowed
osGetGridNick			always allowed
osListSortInPlaceStrided	always allowed
osMakeNotecard			${OSSL|osslParcelO}ESTATE_MANAGER,ESTATE_OWNER
```

## General Process
[Back to Top](#os-partnership)

Terms (for simplification of explanations):
- Agent = avatar proposing partnership
- Avatar = the target of the proposal

A. For a Partnered agent:
- Offer to Dissolve partnership
- if Dissolve partnership requested, perform dissolve and inform both parties

B. For an unpartnered agent:
- Present a list of (up to) 9 closest avatars to choose from
- Send dialog to chosen avatar, asking them to Accept or Decline proposal
- If declined, inform agent
- If accepted, perform partnering
- If successfully partnered, shout a congratulatory message and fire a party-popper script
- Both agent and avatar receive a certificate

If the chosen partner is already partnered, the agent is informed the partnership failed (and why). The avatar is informed they must first dissolve their existing partnership.

Once the agent has chosen a partner, the script goes into lockdown to prevent anyone else using it while the current partnering process is taking place. A partnering process takes priority over a dissolution.

## Files not included
[Back to Top](#os-partnership)

You can include a party-popper script, which may fire some celebratory particles, play a fanfare and perhaps firework sounds.

The script can be placed anywhere in the link set, and should include the following minimal code:
```lsl2
integer PARTY_POPPERS = -10000;

partyPopper(){
    // fire some particles
    // play some sounds
}

default {
    link_message( integer sender, integer num, string msg, key id ){
        if( num == PARTY_POPPERS ){
            partyPopper();
        }
    }
}
```

Then just populate the `partyPopper()` function with some appropriate code.

## The Certificate
[Back to Top](#os-partnership)

The certificate is generated from a template, stored in the notecard `Partnership.txt` which can be edited to suit your needs. The certificate generator recognises certain tokens that are replaced at runtime:

- %d (date)
- %t (time)
- %r (region name)
- %g (grid name)
- %n (grid nickname)
- %a (name of proposing avatar)
- %b (name of target avatar)
- %ua (uuid of proposing avatar)
- %ub (uuid of target avatar)

In the default template, there are some notes on forcing an update to profiles if the partnering/dissolving doesn't show. These will be included in the certficate.

## The Secret
[Back to Top](#os-partnership)

There is a `secret` encoded in both the LSL/OSSL script and the receiving PHP script. If the PHP script doesn't receive the correct `secret`, it will terminate and return an error code of zero (nothing happened) and three error messages. None of these will be displayed by the LSL/OSSL script unless debugging is enabled.

Nobody else should know your secret, so this should provide a good level of protection against tampering, especially if you use https.

## Setting Up
[Back to Top](#os-partnership)

On the web server:
1. On your web server, copy the files `partnership.php` and the entire `lib` folder to your web space.
2. Edit `lib/db_params.php` and enter the appropriate credentials to access your grid's database
3. Edit `partnership.php` and set the `secret` to something unique (leaving it unchanged is a security risk)

Your webserver folder hierarchy should look as follows:
```
webspace root/
            + partnership.php
            + lib/
                + db_mysql.php
                + db_params.php
                + params.php
                + sendMessage.php

```
You can put this in a sub-folder of your web space, but make sure that the `lib` folder is a sub-folder of the folder that `partnership.php` is in, and that you set the `URL` correctly in the -in-world script.

In-world:
1. Create a prim or link-set
2. In its inventory, add the notecard `Partnership.txt`
3. In its inventory, create a new script, empty it and copy the contents of `partnership.ossl` into it
4. Edit the script and:
   - set the `secret` to match the `secret` you set in `partnership.php`
   - set the constant `URL` to your website's address (change to http if not using https!)

You should now be able to test. See notes on debugging below.

## Debugging
[Back to Top](#os-partnership)

To enable debugging, set the debugging constant in the LSL/OSSL script to TRUE:
```lsl2
constant __DEBUG__ = TRUE;
```

You can send strings, keys and integers to the debug function:
```lsl2
debug( "a string" );
debug(5);              // it works, no idea why
debug( llGetOwner() ); // output your own key
```
Output is sent to the owner of the task.

Debugging return values from the php script is as simple as adding a line straight after the `http_response()` event is triggered:
```lsl2
    http_response( key id, integer status, list metadata, string body ){
        debug("["+status+"] "+body);
        // etc
    }
```

Note the php script always returns a string in the format:
```lsl2
result = "count|uuid1|uuid2";
```

In some cases, there may be an extra piece of information tacked onto the end:
```lsl2
result = "count|uuid1|uuid2|uuid3";
```

- uuid1 equates to the agent initiating the task
- uuid2 equates to the target avatar
- uuid3 equates to uuid2's partner if they already have one

`count` is always zero unless records have been changed, in which case this should always be 2.

If the test for the `secret` key fails, the result looks like this:
```lsl2
result = "0|Epic Failure|Very Epic Failure|Truly Epic Failure";
```

Again, the zero indicates that nothing was changed.


## Using the Proxy Script
[Back to Top](#os-partnership)

If you are running a grid on your home network, but have a domain elsewhere, you can place the library files and the script from the proxy folder on your website. You can then call the proxy script simply by changing the URL (the name of the proxy script is the same as the main PHP script - `partnership.php`).

This acts as a pass-through, calling the script on your local network (you'll need to update the url it points to).

This is also helpful if you have HTTPS available on your remote website, but need to use HTTP to call your local script.

All the potential parameters are forwarded by the proxy script, and whatever result is returned to it, it will return to your in-world script.

## Using XEngine
[Back to Top](#os-partnership)

The in-world script assumes you will be using the `YEngine` scripting engine on your simulator, and uses certain scripting features available only to that engine. If you are using `XEngine`, you will need to make a few minor changes to the in-world script, in addition to the edits mentioned in the [Setting Up](#setting-up) section.

Edit the in-world script:
1. Change the first line from `//YEngine:` to `XEngine:`
2. Comment out the line `yoptions;`
3. Change the word `constant` to `string` for the following `constants`:
   - source_version
   - secret
   - URL
   - URI
   - certificate
4. Change word `constant` to `integer` for the following `constants`
   - \_\_DEBUG\_\_
   - RES_RESULT
   - RES_USER1
   - RES_USER2
   - PARTY_POPPERS

[Back to Top](#os-partnership)
