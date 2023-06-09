<h1 align="center">Wordpress Vercel Builds</h1>
<br />
<p align="center">
  <a href="https://github.com/TimeZHero/wp-vercel-builds">
    <img src="./screenshot.png" alt="Logo">
  </a>

<p align="center">
    Are we there yet?
  </p>
</p>

## Install

1. Add the following into your composer.json `repositories` key

```
{ 
    "type": "github", 
    "url": "https://github.com/TimeZHero/wp-vercel-builds" 
}
```

2. Run `composer require "timezhero/vercel-builds"`
3. Configure a Vercel webhook pointing to `https://yoursite.com/wp-json/builds/update`
4. Define a constant `VERCEL_SIGNATURE_KEY` with the Vercel key created in Team > Settings > Webhooks
5. Define a constant `VERCEL_API_BEARER_TOKEN` with the Vercel token created in Account > Tokens
6. Activate the plugin and check out your dashboard tab

## Hooks
1. `vercel_builds_capability`, to set the capability to view the build dashboard. The badge will be viewable to anyone. default: manage_options
2. `vercel_builds_log_tag`, to set the tag used to identify which logs to display in the admin dashboard. default: [build_error]
3. `vercel_builds_log_stream`, to set the logstream used on Vercel. default: stderr

## If you have multiple Vercel projects

Vercel currently has a limit of 20 configurable webhooks, which may not be enough for all your projects and environments. You can work around it by setting up a Lambda function on AWS (or equivalent) as the general webhook receiver for all your projects and proxy the request to the correct server.

A `lambda.mjs` file is included in the repo as an example of a possible implementation, but don't forget to:
1. Configure the environment variable `VERCEL_SIGNATURE` with the value provided by Vercel
2. Review the configuration array with all your projects and environments

## Contents
1. Polling on badge to quickly know the state of the latest build
2. Can view builds, with their status, date and duration
3. Can view custom build logs in case the build failed
4. Customer-side debugging is possible by reviewing previous versions from the url
5. The commit SHA is included to help spot any regression bug

## Known Issues
- Due to the absence of a queued deployment event, the following problems arise:
  1. Building status from a queued deployment may be overridden by the previous build's result until the queued deployment is completed
  2. A deployment which was created under the queued status will display its duration equal to the time in the queue + the effective build time

## Roadmap
1. Add a button to allow the customer to pin a previous deployment as production
2. Someday, refactor for better code
