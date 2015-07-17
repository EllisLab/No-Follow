# No-Follow

Looks for hyperlinks in the text and adds `rel="nofollow"` attribute to them.

## Usage

### `{exp:no_follow}`

#### Example Usage

```
{exp:no_follow}

    A <a href="http://www.evilsite.com">link</a> from Spammers

{/exp:no_follow}
```

Returns `A <a href="http://www.evilsite.com" rel="nofollow">link</a>`.

#### Parameters

- `group` - Allows you to specify member groups whose members' URLs will be ignored by this plugin.
- `time` - Allows you to specify how much time (in days) a member account must be active before its member url will be ignored.
This allows newly created member accounts to be reviewed for a short period before their urls are ignored. Only works if the group
parameter is set.
- `whitelist` - (y/n) Allows you to use the ExpressionEngine Whitelist to ignore URLs that are whitelisted.

## Change Log

### 2.0

- Updated plugin to be 3.0 compatible

### 1.1

- Updated plugin to be 2.0 compatible
