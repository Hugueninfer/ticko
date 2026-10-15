<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Ticko API</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8090";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.11.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.11.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-ai-suggestions" class="tocify-header">
                <li class="tocify-item level-1" data-unique="ai-suggestions">
                    <a href="#ai-suggestions">AI suggestions</a>
                </li>
                                    <ul id="tocify-subheader-ai-suggestions" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="ai-suggestions-GETapi-v1-tickets--ticket_id--suggestions-latest">
                                <a href="#ai-suggestions-GETapi-v1-tickets--ticket_id--suggestions-latest">Latest suggestion of a ticket</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="ai-suggestions-POSTapi-v1-tickets--ticket_id--suggestions">
                                <a href="#ai-suggestions-POSTapi-v1-tickets--ticket_id--suggestions">Ask for a suggestion</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="ai-suggestions-POSTapi-v1-suggestions--suggestion_id--approve">
                                <a href="#ai-suggestions-POSTapi-v1-suggestions--suggestion_id--approve">Approve and send</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="ai-suggestions-POSTapi-v1-suggestions--suggestion_id--discard">
                                <a href="#ai-suggestions-POSTapi-v1-suggestions--suggestion_id--discard">Discard</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="ai-suggestions-POSTapi-v1-suggestions--suggestion_id--gap">
                                <a href="#ai-suggestions-POSTapi-v1-suggestions--suggestion_id--gap">Report a knowledge gap</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-attachments" class="tocify-header">
                <li class="tocify-item level-1" data-unique="attachments">
                    <a href="#attachments">Attachments</a>
                </li>
                                    <ul id="tocify-subheader-attachments" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="attachments-GETapi-v1-attachments--attachment_id--content">
                                <a href="#attachments-GETapi-v1-attachments--attachment_id--content">Download attachment content</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="attachments-GETapi-v1-tickets--ticket_id--attachments">
                                <a href="#attachments-GETapi-v1-tickets--ticket_id--attachments">List attachments of a ticket</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="attachments-POSTapi-v1-tickets--ticket_id--attachments">
                                <a href="#attachments-POSTapi-v1-tickets--ticket_id--attachments">Upload an image</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="attachments-GETapi-v1-attachments--id-">
                                <a href="#attachments-GETapi-v1-attachments--id-">Show an attachment</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-authentication" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authentication">
                    <a href="#authentication">Authentication</a>
                </li>
                                    <ul id="tocify-subheader-authentication" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-login">
                                <a href="#authentication-POSTapi-v1-auth-login">Log in (SPA session)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-tokens">
                                <a href="#authentication-POSTapi-v1-auth-tokens">Issue an API token (mobile)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-GETapi-v1-auth-demo">
                                <a href="#authentication-GETapi-v1-auth-demo">Demo sign-in availability</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-demo">
                                <a href="#authentication-POSTapi-v1-auth-demo">Sign in as a demo user (SPA session)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-demo-tokens">
                                <a href="#authentication-POSTapi-v1-auth-demo-tokens">Demo customer token (mobile)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-logout">
                                <a href="#authentication-POSTapi-v1-auth-logout">Log out (SPA session)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-DELETEapi-v1-auth-tokens-current">
                                <a href="#authentication-DELETEapi-v1-auth-tokens-current">Revoke the current API token</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-GETapi-v1-auth-me">
                                <a href="#authentication-GETapi-v1-auth-me">Current user</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-PUTapi-v1-auth-password">
                                <a href="#authentication-PUTapi-v1-auth-password">Change password</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-PUTapi-v1-auth-notification-preferences">
                                <a href="#authentication-PUTapi-v1-auth-notification-preferences">Update notification preferences</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-categories" class="tocify-header">
                <li class="tocify-item level-1" data-unique="categories">
                    <a href="#categories">Categories</a>
                </li>
                                    <ul id="tocify-subheader-categories" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="categories-GETapi-v1-categories">
                                <a href="#categories-GETapi-v1-categories">List categories</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="categories-POSTapi-v1-categories">
                                <a href="#categories-POSTapi-v1-categories">Create a category</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="categories-GETapi-v1-categories--id-">
                                <a href="#categories-GETapi-v1-categories--id-">Show a category</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="categories-PUTapi-v1-categories--id-">
                                <a href="#categories-PUTapi-v1-categories--id-">Update a category</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="categories-DELETEapi-v1-categories--id-">
                                <a href="#categories-DELETEapi-v1-categories--id-">Delete a category</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-broadcasting-auth">
                                <a href="#endpoints-GETapi-broadcasting-auth">Authenticate the request for channel access.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-knowledge-base" class="tocify-header">
                <li class="tocify-item level-1" data-unique="knowledge-base">
                    <a href="#knowledge-base">Knowledge base</a>
                </li>
                                    <ul id="tocify-subheader-knowledge-base" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="knowledge-base-GETapi-v1-knowledge-stats">
                                <a href="#knowledge-base-GETapi-v1-knowledge-stats">Knowledge-base figures</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-POSTapi-v1-knowledge-search">
                                <a href="#knowledge-base-POSTapi-v1-knowledge-search">Search the knowledge base (playground)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-POSTapi-v1-knowledge-articles-import">
                                <a href="#knowledge-base-POSTapi-v1-knowledge-articles-import">Import a Markdown file (draft)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-GETapi-v1-knowledge-articles">
                                <a href="#knowledge-base-GETapi-v1-knowledge-articles">List articles</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-POSTapi-v1-knowledge-articles">
                                <a href="#knowledge-base-POSTapi-v1-knowledge-articles">Create an article (draft)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-GETapi-v1-knowledge-articles--id-">
                                <a href="#knowledge-base-GETapi-v1-knowledge-articles--id-">Show an article</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-PUTapi-v1-knowledge-articles--id-">
                                <a href="#knowledge-base-PUTapi-v1-knowledge-articles--id-">Edit an article</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-DELETEapi-v1-knowledge-articles--id-">
                                <a href="#knowledge-base-DELETEapi-v1-knowledge-articles--id-">Delete an article (admins)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-POSTapi-v1-knowledge-articles--article_id--publish">
                                <a href="#knowledge-base-POSTapi-v1-knowledge-articles--article_id--publish">Publish an article</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-POSTapi-v1-knowledge-articles--article_id--unpublish">
                                <a href="#knowledge-base-POSTapi-v1-knowledge-articles--article_id--unpublish">Unpublish an article</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-POSTapi-v1-knowledge-articles--article_id--reindex">
                                <a href="#knowledge-base-POSTapi-v1-knowledge-articles--article_id--reindex">Index again</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="knowledge-base-GETapi-v1-knowledge-articles--article_id--chunks">
                                <a href="#knowledge-base-GETapi-v1-knowledge-articles--article_id--chunks">Indexed passages ("how the AI sees it")</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-metrics" class="tocify-header">
                <li class="tocify-item level-1" data-unique="metrics">
                    <a href="#metrics">Metrics</a>
                </li>
                                    <ul id="tocify-subheader-metrics" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="metrics-GETapi-v1-metrics">
                                <a href="#metrics-GETapi-v1-metrics">Dashboard metrics</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-push-notifications" class="tocify-header">
                <li class="tocify-item level-1" data-unique="push-notifications">
                    <a href="#push-notifications">Push notifications</a>
                </li>
                                    <ul id="tocify-subheader-push-notifications" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="push-notifications-POSTapi-v1-push-devices">
                                <a href="#push-notifications-POSTapi-v1-push-devices">Register this device</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="push-notifications-DELETEapi-v1-push-devices-current">
                                <a href="#push-notifications-DELETEapi-v1-push-devices-current">Unregister this device</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-realtime" class="tocify-header">
                <li class="tocify-item level-1" data-unique="realtime">
                    <a href="#realtime">Realtime</a>
                </li>
                                    <ul id="tocify-subheader-realtime" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="realtime-GETapi-v1-realtime">
                                <a href="#realtime-GETapi-v1-realtime">Realtime settings</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-team" class="tocify-header">
                <li class="tocify-item level-1" data-unique="team">
                    <a href="#team">Team</a>
                </li>
                                    <ul id="tocify-subheader-team" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="team-GETapi-v1-team">
                                <a href="#team-GETapi-v1-team">List members</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="team-POSTapi-v1-team">
                                <a href="#team-POSTapi-v1-team">Add a member</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="team-GETapi-v1-team--member_id-">
                                <a href="#team-GETapi-v1-team--member_id-">Show a member</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="team-PATCHapi-v1-team--member_id-">
                                <a href="#team-PATCHapi-v1-team--member_id-">Update a member</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-ticket-messages" class="tocify-header">
                <li class="tocify-item level-1" data-unique="ticket-messages">
                    <a href="#ticket-messages">Ticket messages</a>
                </li>
                                    <ul id="tocify-subheader-ticket-messages" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="ticket-messages-GETapi-v1-tickets--ticket_id--messages">
                                <a href="#ticket-messages-GETapi-v1-tickets--ticket_id--messages">List messages</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="ticket-messages-POSTapi-v1-tickets--ticket_id--messages">
                                <a href="#ticket-messages-POSTapi-v1-tickets--ticket_id--messages">Post a message</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-tickets" class="tocify-header">
                <li class="tocify-item level-1" data-unique="tickets">
                    <a href="#tickets">Tickets</a>
                </li>
                                    <ul id="tocify-subheader-tickets" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="tickets-GETapi-v1-tickets">
                                <a href="#tickets-GETapi-v1-tickets">List tickets</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="tickets-POSTapi-v1-tickets">
                                <a href="#tickets-POSTapi-v1-tickets">Open a ticket</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="tickets-GETapi-v1-tickets--id-">
                                <a href="#tickets-GETapi-v1-tickets--id-">Show a ticket</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="tickets-PATCHapi-v1-tickets--ticket_id--status">
                                <a href="#tickets-PATCHapi-v1-tickets--ticket_id--status">Change ticket status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="tickets-PATCHapi-v1-tickets--ticket_id--assignee">
                                <a href="#tickets-PATCHapi-v1-tickets--ticket_id--assignee">Assign a ticket</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: October 15, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>Multi-tenant helpdesk API (v1).</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8090</code>
</aside>
<pre><code>Ticko is a multi-tenant helpdesk. Every request is scoped to one tenant:

* **Web (SPA):** call `GET /sanctum/csrf-cookie`, then `POST /api/v1/auth/login` with the `X-Tenant` header (tenant slug). The session cookie carries the tenant afterwards.
* **Mobile / API clients:** `POST /api/v1/auth/tokens` with the `X-Tenant` header, then send `Authorization: Bearer &lt;token&gt;`. The token carries the tenant.

Errors use RFC 7807 problem details (`application/problem+json`). Validation errors add an `errors` object.</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="ai-suggestions">AI suggestions</h1>

    <p>Reply drafts written by the AI from the knowledge base, for agents. The AI never answers a customer by itself: a draft is only sent when an agent approves it.</p>

                                <h2 id="ai-suggestions-GETapi-v1-tickets--ticket_id--suggestions-latest">Latest suggestion of a ticket</h2>

<p>
</p>

<p><code>data</code> is null when none was requested yet.</p>

<span id="example-requests-GETapi-v1-tickets--ticket_id--suggestions-latest">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/tickets/architecto/suggestions/latest" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto/suggestions/latest"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-tickets--ticket_id--suggestions-latest">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5tzrday55zj73wwg508&quot;,
        &quot;ticket_id&quot;: &quot;01m50gf5txe7rbd3w0c92gyyq2&quot;,
        &quot;status&quot;: &quot;ready&quot;,
        &quot;body&quot;: &quot;Ol&aacute;! O prazo &eacute; de at&eacute; 2 dias &uacute;teis [1].&quot;,
        &quot;confidence&quot;: 0.85,
        &quot;citations&quot;: [],
        &quot;candidates&quot;: [],
        &quot;error_code&quot;: null,
        &quot;provider&quot;: &quot;fake&quot;,
        &quot;model&quot;: &quot;fake&quot;,
        &quot;latency_ms&quot;: null,
        &quot;edited&quot;: false,
        &quot;discard_reason&quot;: null,
        &quot;reviewed_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5ty35qz15k3fw6kbxfy&quot;,
            &quot;name&quot;: &quot;Simeon Willms&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;reviewed_at&quot;: null,
        &quot;message_id&quot;: null,
        &quot;gap_reported_at&quot;: null,
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-tickets--ticket_id--suggestions-latest" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-tickets--ticket_id--suggestions-latest"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-tickets--ticket_id--suggestions-latest"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-tickets--ticket_id--suggestions-latest" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-tickets--ticket_id--suggestions-latest">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-tickets--ticket_id--suggestions-latest" data-method="GET"
      data-path="api/v1/tickets/{ticket_id}/suggestions/latest"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-tickets--ticket_id--suggestions-latest', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-tickets--ticket_id--suggestions-latest"
                    onclick="tryItOut('GETapi-v1-tickets--ticket_id--suggestions-latest');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-tickets--ticket_id--suggestions-latest"
                    onclick="cancelTryOut('GETapi-v1-tickets--ticket_id--suggestions-latest');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-tickets--ticket_id--suggestions-latest"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/tickets/{ticket_id}/suggestions/latest</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-tickets--ticket_id--suggestions-latest"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-tickets--ticket_id--suggestions-latest"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_id"                data-endpoint="GETapi-v1-tickets--ticket_id--suggestions-latest"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>ready</code></li> <li><code>insufficient</code></li> <li><code>failed</code></li> <li><code>approved</code></li> <li><code>discarded</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Draft with source markers like [1] (removed when sent).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>confidence</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>error_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>e.g. LLM_TIMEOUT when the provider failed.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>provider</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>model</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>latency_ms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>discard_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>wrong_information</code></li> <li><code>tone</code></li> <li><code>outdated_source</code></li> <li><code>other</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>message_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>gap_reported_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                        <h2 id="ai-suggestions-POSTapi-v1-tickets--ticket_id--suggestions">Ask for a suggestion</h2>

<p>
</p>

<p>The draft is written in the background; follow it with the latest
suggestion endpoint or the staff realtime channel.</p>

<span id="example-requests-POSTapi-v1-tickets--ticket_id--suggestions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/tickets/architecto/suggestions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto/suggestions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-tickets--ticket_id--suggestions">
            <blockquote>
            <p>Example response (202):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5v5yqv448t2gkmf9c5c&quot;,
        &quot;ticket_id&quot;: &quot;01m50gf5v4rh4kv78w4057pycx&quot;,
        &quot;status&quot;: &quot;ready&quot;,
        &quot;body&quot;: &quot;Ol&aacute;! O prazo &eacute; de at&eacute; 2 dias &uacute;teis [1].&quot;,
        &quot;confidence&quot;: 0.85,
        &quot;citations&quot;: [],
        &quot;candidates&quot;: [],
        &quot;error_code&quot;: null,
        &quot;provider&quot;: &quot;fake&quot;,
        &quot;model&quot;: &quot;fake&quot;,
        &quot;latency_ms&quot;: null,
        &quot;edited&quot;: false,
        &quot;discard_reason&quot;: null,
        &quot;reviewed_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5v5yqv448t2gkmf9c5b&quot;,
            &quot;name&quot;: &quot;Cole White I&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;reviewed_at&quot;: null,
        &quot;message_id&quot;: null,
        &quot;gap_reported_at&quot;: null,
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-tickets--ticket_id--suggestions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-tickets--ticket_id--suggestions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-tickets--ticket_id--suggestions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-tickets--ticket_id--suggestions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-tickets--ticket_id--suggestions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-tickets--ticket_id--suggestions" data-method="POST"
      data-path="api/v1/tickets/{ticket_id}/suggestions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-tickets--ticket_id--suggestions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-tickets--ticket_id--suggestions"
                    onclick="tryItOut('POSTapi-v1-tickets--ticket_id--suggestions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-tickets--ticket_id--suggestions"
                    onclick="cancelTryOut('POSTapi-v1-tickets--ticket_id--suggestions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-tickets--ticket_id--suggestions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/tickets/{ticket_id}/suggestions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-tickets--ticket_id--suggestions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-tickets--ticket_id--suggestions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_id"                data-endpoint="POSTapi-v1-tickets--ticket_id--suggestions"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>ready</code></li> <li><code>insufficient</code></li> <li><code>failed</code></li> <li><code>approved</code></li> <li><code>discarded</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Draft with source markers like [1] (removed when sent).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>confidence</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>error_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>e.g. LLM_TIMEOUT when the provider failed.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>provider</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>model</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>latency_ms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>discard_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>wrong_information</code></li> <li><code>tone</code></li> <li><code>outdated_source</code></li> <li><code>other</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>message_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>gap_reported_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                        <h2 id="ai-suggestions-POSTapi-v1-suggestions--suggestion_id--approve">Approve and send</h2>

<p>
</p>

<p>Posts the (possibly edited) draft as the agent's public reply.</p>

<span id="example-requests-POSTapi-v1-suggestions--suggestion_id--approve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/suggestions/architecto/approve" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"body\": \"Oi, Mariana! O prazo é de até 2 dias úteis.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/suggestions/architecto/approve"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "body": "Oi, Mariana! O prazo é de até 2 dias úteis."
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-suggestions--suggestion_id--approve">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5vc74vfkwcag2h8tx9q&quot;,
        &quot;ticket_id&quot;: &quot;01m50gf5va4smt7az1m87s4q99&quot;,
        &quot;status&quot;: &quot;ready&quot;,
        &quot;body&quot;: &quot;Ol&aacute;! O prazo &eacute; de at&eacute; 2 dias &uacute;teis [1].&quot;,
        &quot;confidence&quot;: 0.85,
        &quot;citations&quot;: [],
        &quot;candidates&quot;: [],
        &quot;error_code&quot;: null,
        &quot;provider&quot;: &quot;fake&quot;,
        &quot;model&quot;: &quot;fake&quot;,
        &quot;latency_ms&quot;: null,
        &quot;edited&quot;: false,
        &quot;discard_reason&quot;: null,
        &quot;reviewed_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5vbf1cf4p8prpjjrrf8&quot;,
            &quot;name&quot;: &quot;Prof. Juvenal O&#039;Kon&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;reviewed_at&quot;: null,
        &quot;message_id&quot;: null,
        &quot;gap_reported_at&quot;: null,
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-suggestions--suggestion_id--approve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-suggestions--suggestion_id--approve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-suggestions--suggestion_id--approve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-suggestions--suggestion_id--approve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-suggestions--suggestion_id--approve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-suggestions--suggestion_id--approve" data-method="POST"
      data-path="api/v1/suggestions/{suggestion_id}/approve"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-suggestions--suggestion_id--approve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-suggestions--suggestion_id--approve"
                    onclick="tryItOut('POSTapi-v1-suggestions--suggestion_id--approve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-suggestions--suggestion_id--approve"
                    onclick="cancelTryOut('POSTapi-v1-suggestions--suggestion_id--approve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-suggestions--suggestion_id--approve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/suggestions/{suggestion_id}/approve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>suggestion_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="suggestion_id"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--approve"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the suggestion. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--approve"
               value="Oi, Mariana! O prazo é de até 2 dias úteis."
               data-component="body">
    <br>
<p>The reply as the agent wants to send it (the draft, possibly edited). Must not be greater than 20000 characters. Example: <code>Oi, Mariana! O prazo é de até 2 dias úteis.</code></p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>ready</code></li> <li><code>insufficient</code></li> <li><code>failed</code></li> <li><code>approved</code></li> <li><code>discarded</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Draft with source markers like [1] (removed when sent).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>confidence</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>error_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>e.g. LLM_TIMEOUT when the provider failed.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>provider</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>model</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>latency_ms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>discard_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>wrong_information</code></li> <li><code>tone</code></li> <li><code>outdated_source</code></li> <li><code>other</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>message_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>gap_reported_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                        <h2 id="ai-suggestions-POSTapi-v1-suggestions--suggestion_id--discard">Discard</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-suggestions--suggestion_id--discard">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/suggestions/architecto/discard" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"tone\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/suggestions/architecto/discard"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "tone"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-suggestions--suggestion_id--discard">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5vj8zqqar9kefd4518h&quot;,
        &quot;ticket_id&quot;: &quot;01m50gf5vgd25w501xk62ck65z&quot;,
        &quot;status&quot;: &quot;ready&quot;,
        &quot;body&quot;: &quot;Ol&aacute;! O prazo &eacute; de at&eacute; 2 dias &uacute;teis [1].&quot;,
        &quot;confidence&quot;: 0.85,
        &quot;citations&quot;: [],
        &quot;candidates&quot;: [],
        &quot;error_code&quot;: null,
        &quot;provider&quot;: &quot;fake&quot;,
        &quot;model&quot;: &quot;fake&quot;,
        &quot;latency_ms&quot;: null,
        &quot;edited&quot;: false,
        &quot;discard_reason&quot;: null,
        &quot;reviewed_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5vh3pw5993my6m5nq1n&quot;,
            &quot;name&quot;: &quot;Lambert Watsica&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;reviewed_at&quot;: null,
        &quot;message_id&quot;: null,
        &quot;gap_reported_at&quot;: null,
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-suggestions--suggestion_id--discard" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-suggestions--suggestion_id--discard"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-suggestions--suggestion_id--discard"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-suggestions--suggestion_id--discard" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-suggestions--suggestion_id--discard">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-suggestions--suggestion_id--discard" data-method="POST"
      data-path="api/v1/suggestions/{suggestion_id}/discard"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-suggestions--suggestion_id--discard', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-suggestions--suggestion_id--discard"
                    onclick="tryItOut('POSTapi-v1-suggestions--suggestion_id--discard');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-suggestions--suggestion_id--discard"
                    onclick="cancelTryOut('POSTapi-v1-suggestions--suggestion_id--discard');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-suggestions--suggestion_id--discard"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/suggestions/{suggestion_id}/discard</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--discard"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--discard"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>suggestion_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="suggestion_id"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--discard"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the suggestion. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--discard"
               value="tone"
               data-component="body">
    <br>
<p>wrong_information, tone, outdated_source or other. Example: <code>tone</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>wrong_information</code></li> <li><code>tone</code></li> <li><code>outdated_source</code></li> <li><code>other</code></li></ul>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>ready</code></li> <li><code>insufficient</code></li> <li><code>failed</code></li> <li><code>approved</code></li> <li><code>discarded</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Draft with source markers like [1] (removed when sent).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>confidence</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>error_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>e.g. LLM_TIMEOUT when the provider failed.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>provider</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>model</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>latency_ms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>discard_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>wrong_information</code></li> <li><code>tone</code></li> <li><code>outdated_source</code></li> <li><code>other</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>message_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>gap_reported_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                        <h2 id="ai-suggestions-POSTapi-v1-suggestions--suggestion_id--gap">Report a knowledge gap</h2>

<p>
</p>

<p>The knowledge base had no answer for this ticket.</p>

<span id="example-requests-POSTapi-v1-suggestions--suggestion_id--gap">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/suggestions/architecto/gap" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/suggestions/architecto/gap"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-suggestions--suggestion_id--gap">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5vqbweg8ams9gsqtemd&quot;,
        &quot;ticket_id&quot;: &quot;01m50gf5vpxmtpsfss46g9qaby&quot;,
        &quot;status&quot;: &quot;ready&quot;,
        &quot;body&quot;: &quot;Ol&aacute;! O prazo &eacute; de at&eacute; 2 dias &uacute;teis [1].&quot;,
        &quot;confidence&quot;: 0.85,
        &quot;citations&quot;: [],
        &quot;candidates&quot;: [],
        &quot;error_code&quot;: null,
        &quot;provider&quot;: &quot;fake&quot;,
        &quot;model&quot;: &quot;fake&quot;,
        &quot;latency_ms&quot;: null,
        &quot;edited&quot;: false,
        &quot;discard_reason&quot;: null,
        &quot;reviewed_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5vqbweg8ams9gsqtemc&quot;,
            &quot;name&quot;: &quot;Briana Konopelski&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;reviewed_at&quot;: null,
        &quot;message_id&quot;: null,
        &quot;gap_reported_at&quot;: null,
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-suggestions--suggestion_id--gap" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-suggestions--suggestion_id--gap"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-suggestions--suggestion_id--gap"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-suggestions--suggestion_id--gap" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-suggestions--suggestion_id--gap">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-suggestions--suggestion_id--gap" data-method="POST"
      data-path="api/v1/suggestions/{suggestion_id}/gap"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-suggestions--suggestion_id--gap', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-suggestions--suggestion_id--gap"
                    onclick="tryItOut('POSTapi-v1-suggestions--suggestion_id--gap');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-suggestions--suggestion_id--gap"
                    onclick="cancelTryOut('POSTapi-v1-suggestions--suggestion_id--gap');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-suggestions--suggestion_id--gap"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/suggestions/{suggestion_id}/gap</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--gap"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--gap"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>suggestion_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="suggestion_id"                data-endpoint="POSTapi-v1-suggestions--suggestion_id--gap"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the suggestion. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>ready</code></li> <li><code>insufficient</code></li> <li><code>failed</code></li> <li><code>approved</code></li> <li><code>discarded</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Draft with source markers like [1] (removed when sent).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>confidence</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>error_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>e.g. LLM_TIMEOUT when the provider failed.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>provider</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>model</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>latency_ms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>discard_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>wrong_information</code></li> <li><code>tone</code></li> <li><code>outdated_source</code></li> <li><code>other</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reviewed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>message_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>gap_reported_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                    <h1 id="attachments">Attachments</h1>

    <p>Images attached to tickets. Files are stored privately and served through short-lived signed URLs.</p>

                                <h2 id="attachments-GETapi-v1-attachments--attachment_id--content">Download attachment content</h2>

<p>
</p>

<p>Only reachable through the signed <code>url</code> returned by the other attachment
endpoints; the signature carries the authorization and expires quickly.</p>

<span id="example-requests-GETapi-v1-attachments--attachment_id--content">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/attachments/architecto/content" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/attachments/architecto/content"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-attachments--attachment_id--content">
    </span>
<span id="execution-results-GETapi-v1-attachments--attachment_id--content" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-attachments--attachment_id--content"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-attachments--attachment_id--content"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-attachments--attachment_id--content" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-attachments--attachment_id--content">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-attachments--attachment_id--content" data-method="GET"
      data-path="api/v1/attachments/{attachment_id}/content"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-attachments--attachment_id--content', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-attachments--attachment_id--content"
                    onclick="tryItOut('GETapi-v1-attachments--attachment_id--content');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-attachments--attachment_id--content"
                    onclick="cancelTryOut('GETapi-v1-attachments--attachment_id--content');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-attachments--attachment_id--content"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/attachments/{attachment_id}/content</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-attachments--attachment_id--content"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-attachments--attachment_id--content"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>attachment_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="attachment_id"                data-endpoint="GETapi-v1-attachments--attachment_id--content"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the attachment. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="attachments-GETapi-v1-tickets--ticket_id--attachments">List attachments of a ticket</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-tickets--ticket_id--attachments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/tickets/architecto/attachments" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto/attachments"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-tickets--ticket_id--attachments">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;01m50gf5pvh04pzgpr5mfe73xy&quot;,
            &quot;ticket_id&quot;: &quot;01m50gf5ptkxenef1srh5cy3dr&quot;,
            &quot;uploader_id&quot;: &quot;01m50gf5ptkxenef1srh5cy3ds&quot;,
            &quot;original_name&quot;: &quot;screenshot.png&quot;,
            &quot;mime_type&quot;: &quot;image/png&quot;,
            &quot;size&quot;: 1024,
            &quot;width&quot;: 10,
            &quot;height&quot;: 10,
            &quot;url&quot;: &quot;http://localhost:8090/api/v1/attachments/01m50gf5pvh04pzgpr5mfe73xy/content?expires=1792092678&amp;tenant=01m50gf5bw9yqgmjhbgb9yc85g&amp;signature=8b6fde1b496388e2f17fff87e80d990a29ff23514e0c85f4feaa9d8c49ca091d&quot;,
            &quot;url_expires_in&quot;: 300,
            &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
        },
        {
            &quot;id&quot;: &quot;01m50gf5pynnyscv4nndzvyk4k&quot;,
            &quot;ticket_id&quot;: &quot;01m50gf5px9te129eq3kjnbh0e&quot;,
            &quot;uploader_id&quot;: &quot;01m50gf5px9te129eq3kjnbh0f&quot;,
            &quot;original_name&quot;: &quot;screenshot.png&quot;,
            &quot;mime_type&quot;: &quot;image/png&quot;,
            &quot;size&quot;: 1024,
            &quot;width&quot;: 10,
            &quot;height&quot;: 10,
            &quot;url&quot;: &quot;http://localhost:8090/api/v1/attachments/01m50gf5pynnyscv4nndzvyk4k/content?expires=1792092678&amp;tenant=01m50gf5bw9yqgmjhbgb9yc85g&amp;signature=82c43dbcc4608417e4a8ab8fd5c0074b1d56f9cb407e1c1e66e49cd8b6c57ab3&quot;,
            &quot;url_expires_in&quot;: 300,
            &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-tickets--ticket_id--attachments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-tickets--ticket_id--attachments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-tickets--ticket_id--attachments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-tickets--ticket_id--attachments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-tickets--ticket_id--attachments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-tickets--ticket_id--attachments" data-method="GET"
      data-path="api/v1/tickets/{ticket_id}/attachments"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-tickets--ticket_id--attachments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-tickets--ticket_id--attachments"
                    onclick="tryItOut('GETapi-v1-tickets--ticket_id--attachments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-tickets--ticket_id--attachments"
                    onclick="cancelTryOut('GETapi-v1-tickets--ticket_id--attachments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-tickets--ticket_id--attachments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/tickets/{ticket_id}/attachments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-tickets--ticket_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-tickets--ticket_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_id"                data-endpoint="GETapi-v1-tickets--ticket_id--attachments"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="attachments-POSTapi-v1-tickets--ticket_id--attachments">Upload an image</h2>

<p>
</p>

<p>The image type is detected from its content; the image is re-encoded,
which removes EXIF metadata such as GPS position.</p>

<span id="example-requests-POSTapi-v1-tickets--ticket_id--attachments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/tickets/architecto/attachments" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@/tmp/phpm9d3t510nb57ejiCJCP" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto/attachments"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-tickets--ticket_id--attachments">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5q6q2qj121gw25d5eht&quot;,
        &quot;ticket_id&quot;: &quot;01m50gf5q52y62j3a2wfgrg8ny&quot;,
        &quot;uploader_id&quot;: &quot;01m50gf5q6q2qj121gw25d5ehs&quot;,
        &quot;original_name&quot;: &quot;screenshot.png&quot;,
        &quot;mime_type&quot;: &quot;image/png&quot;,
        &quot;size&quot;: 1024,
        &quot;width&quot;: 10,
        &quot;height&quot;: 10,
        &quot;url&quot;: &quot;http://localhost:8090/api/v1/attachments/01m50gf5q6q2qj121gw25d5eht/content?expires=1792092678&amp;tenant=01m50gf5bw9yqgmjhbgb9yc85g&amp;signature=8dbadd6562e18b4650aee2ff27484c439be0496f284e549efa62aa80ebbc2751&quot;,
        &quot;url_expires_in&quot;: 300,
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-tickets--ticket_id--attachments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-tickets--ticket_id--attachments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-tickets--ticket_id--attachments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-tickets--ticket_id--attachments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-tickets--ticket_id--attachments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-tickets--ticket_id--attachments" data-method="POST"
      data-path="api/v1/tickets/{ticket_id}/attachments"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-tickets--ticket_id--attachments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-tickets--ticket_id--attachments"
                    onclick="tryItOut('POSTapi-v1-tickets--ticket_id--attachments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-tickets--ticket_id--attachments"
                    onclick="cancelTryOut('POSTapi-v1-tickets--ticket_id--attachments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-tickets--ticket_id--attachments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/tickets/{ticket_id}/attachments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-tickets--ticket_id--attachments"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-tickets--ticket_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_id"                data-endpoint="POSTapi-v1-tickets--ticket_id--attachments"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-tickets--ticket_id--attachments"
               value=""
               data-component="body">
    <br>
<p>JPEG, PNG or WebP image (max 5 MB). Metadata is stripped. Must be a file. Must not be greater than 5120 kilobytes. Example: <code>/tmp/phpm9d3t510nb57ejiCJCP</code></p>
        </div>
        </form>

                    <h2 id="attachments-GETapi-v1-attachments--id-">Show an attachment</h2>

<p>
</p>

<p>Returns metadata with a fresh signed download URL.</p>

<span id="example-requests-GETapi-v1-attachments--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/attachments/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/attachments/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-attachments--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5qb2g3dra0aj2sbey5j&quot;,
        &quot;ticket_id&quot;: &quot;01m50gf5qans6j045a4bnc4pjj&quot;,
        &quot;uploader_id&quot;: &quot;01m50gf5qans6j045a4bnc4pjk&quot;,
        &quot;original_name&quot;: &quot;screenshot.png&quot;,
        &quot;mime_type&quot;: &quot;image/png&quot;,
        &quot;size&quot;: 1024,
        &quot;width&quot;: 10,
        &quot;height&quot;: 10,
        &quot;url&quot;: &quot;http://localhost:8090/api/v1/attachments/01m50gf5qb2g3dra0aj2sbey5j/content?expires=1792092678&amp;tenant=01m50gf5bw9yqgmjhbgb9yc85g&amp;signature=32655b883b1bc19611d9dbf307083870d67066484f292a61cbefcfe60fdfdf84&quot;,
        &quot;url_expires_in&quot;: 300,
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-attachments--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-attachments--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-attachments--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-attachments--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-attachments--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-attachments--id-" data-method="GET"
      data-path="api/v1/attachments/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-attachments--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-attachments--id-"
                    onclick="tryItOut('GETapi-v1-attachments--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-attachments--id-"
                    onclick="cancelTryOut('GETapi-v1-attachments--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-attachments--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/attachments/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-attachments--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-attachments--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-v1-attachments--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the attachment. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="authentication">Authentication</h1>

    <p>Session (SPA) and token (mobile) authentication. Send the tenant slug in the X-Tenant header to log in.</p>

                                <h2 id="authentication-POSTapi-v1-auth-login">Log in (SPA session)</h2>

<p>
</p>

<p>Starts a cookie-based session for the first-party web app. Call
<code>GET /sanctum/csrf-cookie</code> first and send the <code>X-XSRF-TOKEN</code> header.</p>

<span id="example-requests-POSTapi-v1-auth-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/auth/login" \
    --header "X-Tenant: acme" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"agent@acme.test\",
    \"password\": \"correct-horse-battery-staple\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/login"
);

const headers = {
    "X-Tenant": "acme",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "agent@acme.test",
    "password": "correct-horse-battery-staple"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-login">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5h3f0rmq3et3syy7cg9&quot;,
        &quot;name&quot;: &quot;Ms. Audra Crooks II&quot;,
        &quot;email&quot;: &quot;gulgowski.asia@example.com&quot;,
        &quot;role&quot;: &quot;customer&quot;,
        &quot;is_active&quot;: true,
        &quot;tenant&quot;: {
            &quot;id&quot;: &quot;01m50gf5bw9yqgmjhbgb9yc85g&quot;,
            &quot;name&quot;: &quot;Example Inc.&quot;,
            &quot;slug&quot;: &quot;docs-example&quot;
        },
        &quot;notification_preferences&quot;: {
            &quot;push_replies&quot;: true,
            &quot;push_status_changes&quot;: true
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-auth-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-login" data-method="POST"
      data-path="api/v1/auth/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-login"
                    onclick="tryItOut('POSTapi-v1-auth-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-login"
                    onclick="cancelTryOut('POSTapi-v1-auth-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Tenant</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Tenant"                data-endpoint="POSTapi-v1-auth-login"
               value="acme"
               data-component="header">
    <br>
<p>Example: <code>acme</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-login"
               value="agent@acme.test"
               data-component="body">
    <br>
<p>Account e-mail. Must be a valid email address. Must not be greater than 254 characters. Example: <code>agent@acme.test</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-auth-login"
               value="correct-horse-battery-staple"
               data-component="body">
    <br>
<p>Account password. Must not be greater than 255 characters. Example: <code>correct-horse-battery-staple</code></p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tenant</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the sign-in endpoints and /auth/me.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>open_tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Open tickets assigned to the member (team listing only).</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="authentication-POSTapi-v1-auth-tokens">Issue an API token (mobile)</h2>

<p>
</p>

<p>Returns a bearer token bound to the user's tenant. Tokens expire and
are revoked when the user is deactivated or changes role.</p>

<span id="example-requests-POSTapi-v1-auth-tokens">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/auth/tokens" \
    --header "X-Tenant: acme" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"agent@acme.test\",
    \"password\": \"correct-horse-battery-staple\",
    \"device_name\": \"Pixel 9\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/tokens"
);

const headers = {
    "X-Tenant": "acme",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "agent@acme.test",
    "password": "correct-horse-battery-staple",
    "device_name": "Pixel 9"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-tokens">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;token&quot;: &quot;01J...|plain-text-token&quot;,
    &quot;token_type&quot;: &quot;Bearer&quot;,
    &quot;expires_in&quot;: 2592000
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-auth-tokens" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-tokens"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-tokens"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-tokens" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-tokens">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-tokens" data-method="POST"
      data-path="api/v1/auth/tokens"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-tokens', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-tokens"
                    onclick="tryItOut('POSTapi-v1-auth-tokens');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-tokens"
                    onclick="cancelTryOut('POSTapi-v1-auth-tokens');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-tokens"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/tokens</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Tenant</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Tenant"                data-endpoint="POSTapi-v1-auth-tokens"
               value="acme"
               data-component="header">
    <br>
<p>Example: <code>acme</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-tokens"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-tokens"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-tokens"
               value="agent@acme.test"
               data-component="body">
    <br>
<p>Account e-mail. Must be a valid email address. Must not be greater than 254 characters. Example: <code>agent@acme.test</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-auth-tokens"
               value="correct-horse-battery-staple"
               data-component="body">
    <br>
<p>Account password. Must not be greater than 255 characters. Example: <code>correct-horse-battery-staple</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-auth-tokens"
               value="Pixel 9"
               data-component="body">
    <br>
<p>Label for the token, e.g. the phone model. Must not be greater than 100 characters. Example: <code>Pixel 9</code></p>
        </div>
        </form>

                    <h2 id="authentication-GETapi-v1-auth-demo">Demo sign-in availability</h2>

<p>
</p>

<p>Lets the web app decide whether to show the demo shortcuts.</p>

<span id="example-requests-GETapi-v1-auth-demo">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/auth/demo" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/demo"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-auth-demo">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;tenant&quot;: &quot;acme&quot;,
        &quot;roles&quot;: [
            &quot;agent&quot;,
            &quot;admin&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-auth-demo" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-auth-demo"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-auth-demo"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-auth-demo" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-auth-demo">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-auth-demo" data-method="GET"
      data-path="api/v1/auth/demo"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-auth-demo', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-auth-demo"
                    onclick="tryItOut('GETapi-v1-auth-demo');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-auth-demo"
                    onclick="cancelTryOut('GETapi-v1-auth-demo');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-auth-demo"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/auth/demo</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-auth-demo"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-auth-demo"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="authentication-POSTapi-v1-auth-demo">Sign in as a demo user (SPA session)</h2>

<p>
</p>

<p>Starts a session as the demo tenant's agent or admin. Same CSRF flow as
the regular session login.</p>

<span id="example-requests-POSTapi-v1-auth-demo">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/auth/demo" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"role\": \"agent\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/demo"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "role": "agent"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-demo">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5hpnkr6npfhmd334hty&quot;,
        &quot;name&quot;: &quot;Ms. Audra Crooks II&quot;,
        &quot;email&quot;: &quot;idickens@example.org&quot;,
        &quot;role&quot;: &quot;customer&quot;,
        &quot;is_active&quot;: true,
        &quot;tenant&quot;: {
            &quot;id&quot;: &quot;01m50gf5bw9yqgmjhbgb9yc85g&quot;,
            &quot;name&quot;: &quot;Example Inc.&quot;,
            &quot;slug&quot;: &quot;docs-example&quot;
        },
        &quot;notification_preferences&quot;: {
            &quot;push_replies&quot;: true,
            &quot;push_status_changes&quot;: true
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-auth-demo" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-demo"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-demo"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-demo" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-demo">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-demo" data-method="POST"
      data-path="api/v1/auth/demo"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-demo', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-demo"
                    onclick="tryItOut('POSTapi-v1-auth-demo');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-demo"
                    onclick="cancelTryOut('POSTapi-v1-auth-demo');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-demo"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/demo</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-demo"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-demo"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="role"                data-endpoint="POSTapi-v1-auth-demo"
               value="agent"
               data-component="body">
    <br>
<p>agent or admin. Example: <code>agent</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tenant</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the sign-in endpoints and /auth/me.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>open_tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Open tickets assigned to the member (team listing only).</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="authentication-POSTapi-v1-auth-demo-tokens">Demo customer token (mobile)</h2>

<p>
</p>

<p>Issues an API token for the demo tenant's customer, so the mobile app
can offer a one-tap demo without shipping a password.</p>

<span id="example-requests-POSTapi-v1-auth-demo-tokens">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/auth/demo/tokens" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"device_name\": \"Pixel 9\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/demo/tokens"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "device_name": "Pixel 9"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-demo-tokens">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;token&quot;: &quot;01J...|plain-text-token&quot;,
    &quot;token_type&quot;: &quot;Bearer&quot;,
    &quot;expires_in&quot;: 2592000
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-auth-demo-tokens" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-demo-tokens"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-demo-tokens"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-demo-tokens" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-demo-tokens">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-demo-tokens" data-method="POST"
      data-path="api/v1/auth/demo/tokens"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-demo-tokens', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-demo-tokens"
                    onclick="tryItOut('POSTapi-v1-auth-demo-tokens');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-demo-tokens"
                    onclick="cancelTryOut('POSTapi-v1-auth-demo-tokens');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-demo-tokens"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/demo/tokens</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-demo-tokens"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-demo-tokens"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-auth-demo-tokens"
               value="Pixel 9"
               data-component="body">
    <br>
<p>Label for the token, e.g. the phone model. Must not be greater than 100 characters. Example: <code>Pixel 9</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-logout">Log out (SPA session)</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/auth/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/logout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-logout">
</span>
<span id="execution-results-POSTapi-v1-auth-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-logout" data-method="POST"
      data-path="api/v1/auth/logout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-logout"
                    onclick="tryItOut('POSTapi-v1-auth-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-logout"
                    onclick="cancelTryOut('POSTapi-v1-auth-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="authentication-DELETEapi-v1-auth-tokens-current">Revoke the current API token</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-auth-tokens-current">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8090/api/v1/auth/tokens/current" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/tokens/current"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-auth-tokens-current">
</span>
<span id="execution-results-DELETEapi-v1-auth-tokens-current" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-auth-tokens-current"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-auth-tokens-current"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-auth-tokens-current" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-auth-tokens-current">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-auth-tokens-current" data-method="DELETE"
      data-path="api/v1/auth/tokens/current"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-auth-tokens-current', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-auth-tokens-current"
                    onclick="tryItOut('DELETEapi-v1-auth-tokens-current');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-auth-tokens-current"
                    onclick="cancelTryOut('DELETEapi-v1-auth-tokens-current');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-auth-tokens-current"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/auth/tokens/current</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-auth-tokens-current"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-auth-tokens-current"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="authentication-GETapi-v1-auth-me">Current user</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-auth-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/auth/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/me"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-auth-me">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5j3t2xjdkp1gz84g1pw&quot;,
        &quot;name&quot;: &quot;Ms. Elisabeth Okuneva&quot;,
        &quot;email&quot;: &quot;aschuster@example.com&quot;,
        &quot;role&quot;: &quot;customer&quot;,
        &quot;is_active&quot;: true,
        &quot;tenant&quot;: {
            &quot;id&quot;: &quot;01m50gf5bw9yqgmjhbgb9yc85g&quot;,
            &quot;name&quot;: &quot;Example Inc.&quot;,
            &quot;slug&quot;: &quot;docs-example&quot;
        },
        &quot;notification_preferences&quot;: {
            &quot;push_replies&quot;: true,
            &quot;push_status_changes&quot;: true
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-auth-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-auth-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-auth-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-auth-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-auth-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-auth-me" data-method="GET"
      data-path="api/v1/auth/me"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-auth-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-auth-me"
                    onclick="tryItOut('GETapi-v1-auth-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-auth-me"
                    onclick="cancelTryOut('GETapi-v1-auth-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-auth-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/auth/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-auth-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-auth-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tenant</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the sign-in endpoints and /auth/me.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>open_tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Open tickets assigned to the member (team listing only).</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="authentication-PUTapi-v1-auth-password">Change password</h2>

<p>
</p>

<p>Revokes every API token of the user except the one making the request.</p>

<span id="example-requests-PUTapi-v1-auth-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8090/api/v1/auth/password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"current_password\": \"correct-horse-battery-staple\",
    \"password\": \"a-new-long-unique-passphrase\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "current_password": "correct-horse-battery-staple",
    "password": "a-new-long-unique-passphrase"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-auth-password">
</span>
<span id="execution-results-PUTapi-v1-auth-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-auth-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-auth-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-auth-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-auth-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-auth-password" data-method="PUT"
      data-path="api/v1/auth/password"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-auth-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-auth-password"
                    onclick="tryItOut('PUTapi-v1-auth-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-auth-password"
                    onclick="cancelTryOut('PUTapi-v1-auth-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-auth-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/auth/password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-auth-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-auth-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>current_password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="current_password"                data-endpoint="PUTapi-v1-auth-password"
               value="correct-horse-battery-staple"
               data-component="body">
    <br>
<p>The current password. Example: <code>correct-horse-battery-staple</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="PUTapi-v1-auth-password"
               value="a-new-long-unique-passphrase"
               data-component="body">
    <br>
<p>New password (min 12 chars, not found in known breaches). Example: <code>a-new-long-unique-passphrase</code></p>
        </div>
        </form>

                    <h2 id="authentication-PUTapi-v1-auth-notification-preferences">Update notification preferences</h2>

<p>
</p>

<p>Chooses which events send a push notification to the user's devices.</p>

<span id="example-requests-PUTapi-v1-auth-notification-preferences">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8090/api/v1/auth/notification-preferences" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"push_replies\": true,
    \"push_status_changes\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/auth/notification-preferences"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "push_replies": true,
    "push_status_changes": false
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-auth-notification-preferences">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5j99hjqxyfmm7tb2dy0&quot;,
        &quot;name&quot;: &quot;Leda Price&quot;,
        &quot;email&quot;: &quot;theo.hauck@example.com&quot;,
        &quot;role&quot;: &quot;customer&quot;,
        &quot;is_active&quot;: true,
        &quot;tenant&quot;: {
            &quot;id&quot;: &quot;01m50gf5bw9yqgmjhbgb9yc85g&quot;,
            &quot;name&quot;: &quot;Example Inc.&quot;,
            &quot;slug&quot;: &quot;docs-example&quot;
        },
        &quot;notification_preferences&quot;: {
            &quot;push_replies&quot;: true,
            &quot;push_status_changes&quot;: true
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-auth-notification-preferences" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-auth-notification-preferences"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-auth-notification-preferences"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-auth-notification-preferences" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-auth-notification-preferences">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-auth-notification-preferences" data-method="PUT"
      data-path="api/v1/auth/notification-preferences"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-auth-notification-preferences', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-auth-notification-preferences"
                    onclick="tryItOut('PUTapi-v1-auth-notification-preferences');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-auth-notification-preferences"
                    onclick="cancelTryOut('PUTapi-v1-auth-notification-preferences');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-auth-notification-preferences"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/auth/notification-preferences</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-auth-notification-preferences"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-auth-notification-preferences"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>push_replies</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
 &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-auth-notification-preferences" style="display: none">
            <input type="radio" name="push_replies"
                   value="true"
                   data-endpoint="PUTapi-v1-auth-notification-preferences"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-auth-notification-preferences" style="display: none">
            <input type="radio" name="push_replies"
                   value="false"
                   data-endpoint="PUTapi-v1-auth-notification-preferences"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Push when the team replies to one of your tickets. Example: <code>true</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>push_status_changes</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
 &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-auth-notification-preferences" style="display: none">
            <input type="radio" name="push_status_changes"
                   value="true"
                   data-endpoint="PUTapi-v1-auth-notification-preferences"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-auth-notification-preferences" style="display: none">
            <input type="radio" name="push_status_changes"
                   value="false"
                   data-endpoint="PUTapi-v1-auth-notification-preferences"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Push when the status of one of your tickets changes. Example: <code>false</code></p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tenant</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the sign-in endpoints and /auth/me.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>open_tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Open tickets assigned to the member (team listing only).</p>
                    </div>
                                    </details>
        </div>
                    <h1 id="categories">Categories</h1>

    <p>Everyone in the tenant can list categories; only admins manage them.</p>

                                <h2 id="categories-GETapi-v1-categories">List categories</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-categories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/categories" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/categories"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-categories">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;01m50gf5qec5f7ef5jtvpjq6bt&quot;,
            &quot;name&quot;: &quot;Repudiandae Laboriosam&quot;,
            &quot;slug&quot;: &quot;repudiandae-laboriosam&quot;,
            &quot;description&quot;: &quot;Alias tenetur ratione nemo voluptate accusamus ut et.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            },
            &quot;tickets_count&quot;: 1
        },
        {
            &quot;id&quot;: &quot;01m50gf5qhygh1rjtgvds6856r&quot;,
            &quot;name&quot;: &quot;Error Neque&quot;,
            &quot;slug&quot;: &quot;error-neque&quot;,
            &quot;description&quot;: &quot;Et ipsam dolorem et ut dicta vitae assumenda.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            },
            &quot;tickets_count&quot;: 1
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-categories" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-categories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-categories"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-categories" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-categories">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-categories" data-method="GET"
      data-path="api/v1/categories"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-categories', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-categories"
                    onclick="tryItOut('GETapi-v1-categories');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-categories"
                    onclick="cancelTryOut('GETapi-v1-categories');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-categories"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/categories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the category listing.</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="categories-POSTapi-v1-categories">Create a category</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-categories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/categories" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Hardware\",
    \"description\": \"Printers, laptops and peripherals\",
    \"sla_targets\": {
        \"urgent\": {
            \"first_response_minutes\": 15,
            \"resolution_minutes\": 240
        },
        \"low\": {
            \"first_response_minutes\": 1,
            \"resolution_minutes\": 22
        },
        \"normal\": {
            \"first_response_minutes\": 7,
            \"resolution_minutes\": 16
        },
        \"high\": {
            \"first_response_minutes\": 17,
            \"resolution_minutes\": 15
        }
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/categories"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Hardware",
    "description": "Printers, laptops and peripherals",
    "sla_targets": {
        "urgent": {
            "first_response_minutes": 15,
            "resolution_minutes": 240
        },
        "low": {
            "first_response_minutes": 1,
            "resolution_minutes": 22
        },
        "normal": {
            "first_response_minutes": 7,
            "resolution_minutes": 16
        },
        "high": {
            "first_response_minutes": 17,
            "resolution_minutes": 15
        }
    }
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-categories">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5qt03q8sw8a686tb3ds&quot;,
        &quot;name&quot;: &quot;Fugiat Sunt&quot;,
        &quot;slug&quot;: &quot;fugiat-sunt&quot;,
        &quot;description&quot;: &quot;Accusantium harum mollitia modi deserunt aut ab.&quot;,
        &quot;sla_targets&quot;: {
            &quot;low&quot;: {
                &quot;first_response_minutes&quot;: 480,
                &quot;resolution_minutes&quot;: 4320,
                &quot;is_custom&quot;: false
            },
            &quot;normal&quot;: {
                &quot;first_response_minutes&quot;: 240,
                &quot;resolution_minutes&quot;: 1440,
                &quot;is_custom&quot;: false
            },
            &quot;high&quot;: {
                &quot;first_response_minutes&quot;: 60,
                &quot;resolution_minutes&quot;: 480,
                &quot;is_custom&quot;: false
            },
            &quot;urgent&quot;: {
                &quot;first_response_minutes&quot;: 15,
                &quot;resolution_minutes&quot;: 240,
                &quot;is_custom&quot;: false
            }
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-categories" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-categories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-categories"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-categories" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-categories">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-categories" data-method="POST"
      data-path="api/v1/categories"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-categories', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-categories"
                    onclick="tryItOut('POSTapi-v1-categories');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-categories"
                    onclick="cancelTryOut('POSTapi-v1-categories');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-categories"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/categories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-categories"
               value="Hardware"
               data-component="body">
    <br>
<p>Category name, unique per tenant. Must be at least 2 characters. Must not be greater than 80 characters. Example: <code>Hardware</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-categories"
               value="Printers, laptops and peripherals"
               data-component="body">
    <br>
<p>Optional description. Must not be greater than 500 characters. Example: <code>Printers, laptops and peripherals</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>sla_targets</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>SLA overrides keyed by priority (urgent, high, normal, low), each with first_response_minutes and resolution_minutes. Null resets to the defaults.</p>
            </summary>
                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>low</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.low.first_response_minutes"                data-endpoint="POSTapi-v1-categories"
               value="1"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.low</code> is present. Must be at least 1. Must not be greater than 43200. Example: <code>1</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.low.resolution_minutes"                data-endpoint="POSTapi-v1-categories"
               value="22"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.low</code> is present. Must not be greater than 43200. Example: <code>22</code></p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>normal</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.normal.first_response_minutes"                data-endpoint="POSTapi-v1-categories"
               value="7"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.normal</code> is present. Must be at least 1. Must not be greater than 43200. Example: <code>7</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.normal.resolution_minutes"                data-endpoint="POSTapi-v1-categories"
               value="16"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.normal</code> is present. Must not be greater than 43200. Example: <code>16</code></p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>high</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.high.first_response_minutes"                data-endpoint="POSTapi-v1-categories"
               value="17"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.high</code> is present. Must be at least 1. Must not be greater than 43200. Example: <code>17</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.high.resolution_minutes"                data-endpoint="POSTapi-v1-categories"
               value="15"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.high</code> is present. Must not be greater than 43200. Example: <code>15</code></p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>urgent</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.urgent.first_response_minutes"                data-endpoint="POSTapi-v1-categories"
               value="15"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.urgent</code> is present. Must be at least 1. Must not be greater than 43200. Example: <code>15</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.urgent.resolution_minutes"                data-endpoint="POSTapi-v1-categories"
               value="240"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.urgent</code> is present. Must not be greater than 43200. Example: <code>240</code></p>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the category listing.</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="categories-GETapi-v1-categories--id-">Show a category</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/categories/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/categories/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-categories--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5qx79hfcrfnpd0ewp22&quot;,
        &quot;name&quot;: &quot;Eius Et&quot;,
        &quot;slug&quot;: &quot;eius-et&quot;,
        &quot;description&quot;: &quot;Quos velit et fugiat sunt nihil accusantium harum.&quot;,
        &quot;sla_targets&quot;: {
            &quot;low&quot;: {
                &quot;first_response_minutes&quot;: 480,
                &quot;resolution_minutes&quot;: 4320,
                &quot;is_custom&quot;: false
            },
            &quot;normal&quot;: {
                &quot;first_response_minutes&quot;: 240,
                &quot;resolution_minutes&quot;: 1440,
                &quot;is_custom&quot;: false
            },
            &quot;high&quot;: {
                &quot;first_response_minutes&quot;: 60,
                &quot;resolution_minutes&quot;: 480,
                &quot;is_custom&quot;: false
            },
            &quot;urgent&quot;: {
                &quot;first_response_minutes&quot;: 15,
                &quot;resolution_minutes&quot;: 240,
                &quot;is_custom&quot;: false
            }
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-categories--id-" data-method="GET"
      data-path="api/v1/categories/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-categories--id-"
                    onclick="tryItOut('GETapi-v1-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-categories--id-"
                    onclick="cancelTryOut('GETapi-v1-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-v1-categories--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the category listing.</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="categories-PUTapi-v1-categories--id-">Update a category</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8090/api/v1/categories/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Hardware\",
    \"description\": \"Printers, laptops and peripherals\",
    \"sla_targets\": {
        \"urgent\": {
            \"first_response_minutes\": 15,
            \"resolution_minutes\": 240
        },
        \"low\": {
            \"first_response_minutes\": 1,
            \"resolution_minutes\": 22
        },
        \"normal\": {
            \"first_response_minutes\": 7,
            \"resolution_minutes\": 16
        },
        \"high\": {
            \"first_response_minutes\": 17,
            \"resolution_minutes\": 15
        }
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/categories/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Hardware",
    "description": "Printers, laptops and peripherals",
    "sla_targets": {
        "urgent": {
            "first_response_minutes": 15,
            "resolution_minutes": 240
        },
        "low": {
            "first_response_minutes": 1,
            "resolution_minutes": 22
        },
        "normal": {
            "first_response_minutes": 7,
            "resolution_minutes": 16
        },
        "high": {
            "first_response_minutes": 17,
            "resolution_minutes": 15
        }
    }
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-categories--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5r2g4j9j0hf7xs1crdb&quot;,
        &quot;name&quot;: &quot;Sunt Nihil&quot;,
        &quot;slug&quot;: &quot;sunt-nihil&quot;,
        &quot;description&quot;: &quot;Harum mollitia modi deserunt aut ab provident perspiciatis quo.&quot;,
        &quot;sla_targets&quot;: {
            &quot;low&quot;: {
                &quot;first_response_minutes&quot;: 480,
                &quot;resolution_minutes&quot;: 4320,
                &quot;is_custom&quot;: false
            },
            &quot;normal&quot;: {
                &quot;first_response_minutes&quot;: 240,
                &quot;resolution_minutes&quot;: 1440,
                &quot;is_custom&quot;: false
            },
            &quot;high&quot;: {
                &quot;first_response_minutes&quot;: 60,
                &quot;resolution_minutes&quot;: 480,
                &quot;is_custom&quot;: false
            },
            &quot;urgent&quot;: {
                &quot;first_response_minutes&quot;: 15,
                &quot;resolution_minutes&quot;: 240,
                &quot;is_custom&quot;: false
            }
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-categories--id-" data-method="PUT"
      data-path="api/v1/categories/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-categories--id-"
                    onclick="tryItOut('PUTapi-v1-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-categories--id-"
                    onclick="cancelTryOut('PUTapi-v1-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/categories/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-v1-categories--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-v1-categories--id-"
               value="Hardware"
               data-component="body">
    <br>
<p>Category name, unique per tenant. Must be at least 2 characters. Must not be greater than 80 characters. Example: <code>Hardware</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-v1-categories--id-"
               value="Printers, laptops and peripherals"
               data-component="body">
    <br>
<p>Optional description. Must not be greater than 500 characters. Example: <code>Printers, laptops and peripherals</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>sla_targets</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>SLA overrides keyed by priority (urgent, high, normal, low), each with first_response_minutes and resolution_minutes. Null resets to the defaults.</p>
            </summary>
                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>low</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.low.first_response_minutes"                data-endpoint="PUTapi-v1-categories--id-"
               value="1"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.low</code> is present. Must be at least 1. Must not be greater than 43200. Example: <code>1</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.low.resolution_minutes"                data-endpoint="PUTapi-v1-categories--id-"
               value="22"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.low</code> is present. Must not be greater than 43200. Example: <code>22</code></p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>normal</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.normal.first_response_minutes"                data-endpoint="PUTapi-v1-categories--id-"
               value="7"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.normal</code> is present. Must be at least 1. Must not be greater than 43200. Example: <code>7</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.normal.resolution_minutes"                data-endpoint="PUTapi-v1-categories--id-"
               value="16"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.normal</code> is present. Must not be greater than 43200. Example: <code>16</code></p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>high</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.high.first_response_minutes"                data-endpoint="PUTapi-v1-categories--id-"
               value="17"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.high</code> is present. Must be at least 1. Must not be greater than 43200. Example: <code>17</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.high.resolution_minutes"                data-endpoint="PUTapi-v1-categories--id-"
               value="15"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.high</code> is present. Must not be greater than 43200. Example: <code>15</code></p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>urgent</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.urgent.first_response_minutes"                data-endpoint="PUTapi-v1-categories--id-"
               value="15"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.urgent</code> is present. Must be at least 1. Must not be greater than 43200. Example: <code>15</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sla_targets.urgent.resolution_minutes"                data-endpoint="PUTapi-v1-categories--id-"
               value="240"
               data-component="body">
    <br>
<p>This field is required when <code>sla_targets.urgent</code> is present. Must not be greater than 43200. Example: <code>240</code></p>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the category listing.</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="categories-DELETEapi-v1-categories--id-">Delete a category</h2>

<p>
</p>

<p>Tickets in the category are kept and become uncategorized.</p>

<span id="example-requests-DELETEapi-v1-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8090/api/v1/categories/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/categories/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-categories--id-">
</span>
<span id="execution-results-DELETEapi-v1-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-categories--id-" data-method="DELETE"
      data-path="api/v1/categories/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-categories--id-"
                    onclick="tryItOut('DELETEapi-v1-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-categories--id-"
                    onclick="cancelTryOut('DELETEapi-v1-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-v1-categories--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-broadcasting-auth">Authenticate the request for channel access.</h2>

<p>
</p>



<span id="example-requests-GETapi-broadcasting-auth">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/broadcasting/auth" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/broadcasting/auth"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-broadcasting-auth">
    </span>
<span id="execution-results-GETapi-broadcasting-auth" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-broadcasting-auth"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-broadcasting-auth"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-broadcasting-auth" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-broadcasting-auth">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-broadcasting-auth" data-method="GET"
      data-path="api/broadcasting/auth"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-broadcasting-auth', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-broadcasting-auth"
                    onclick="tryItOut('GETapi-broadcasting-auth');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-broadcasting-auth"
                    onclick="cancelTryOut('GETapi-broadcasting-auth');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-broadcasting-auth"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/broadcasting/auth</code></b>
        </p>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/broadcasting/auth</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-broadcasting-auth"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-broadcasting-auth"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="knowledge-base">Knowledge base</h1>

    <p>Markdown articles the AI may cite (staff only). Only published, indexed articles are searched; drafts never are.</p>

                                <h2 id="knowledge-base-GETapi-v1-knowledge-stats">Knowledge-base figures</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-knowledge-stats">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/knowledge/stats" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/stats"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-knowledge-stats">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;published&quot;: 42,
        &quot;chunks&quot;: 386,
        &quot;queued&quot;: 2,
        &quot;failed&quot;: 1,
        &quot;gaps&quot;: 3
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-knowledge-stats" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-knowledge-stats"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-knowledge-stats"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-knowledge-stats" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-knowledge-stats">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-knowledge-stats" data-method="GET"
      data-path="api/v1/knowledge/stats"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-knowledge-stats', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-knowledge-stats"
                    onclick="tryItOut('GETapi-v1-knowledge-stats');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-knowledge-stats"
                    onclick="cancelTryOut('GETapi-v1-knowledge-stats');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-knowledge-stats"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/knowledge/stats</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-knowledge-stats"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-knowledge-stats"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="knowledge-base-POSTapi-v1-knowledge-search">Search the knowledge base (playground)</h2>

<p>
</p>

<p>Hybrid search exactly as the AI uses it: full-text and semantic scores
and the combined score of each passage.</p>

<span id="example-requests-POSTapi-v1-knowledge-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/knowledge/search" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"query\": \"o rastreio do meu pedido parou, o que faço?\",
    \"text_weight\": 0.4,
    \"top_k\": 5,
    \"rerank\": false,
    \"customer_visible_only\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/search"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "query": "o rastreio do meu pedido parou, o que faço?",
    "text_weight": 0.4,
    "top_k": 5,
    "rerank": false,
    "customer_visible_only": false
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-knowledge-search">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;chunk_id&quot;: &quot;01J...&quot;,
            &quot;article_id&quot;: &quot;01J...&quot;,
            &quot;title&quot;: &quot;Pol&iacute;tica de entregas e prazos&quot;,
            &quot;ordinal&quot;: 3,
            &quot;heading&quot;: &quot;Rastreio sem atualiza&ccedil;&atilde;o&quot;,
            &quot;content&quot;: &quot;Se o rastreamento n&atilde;o for atualizado...&quot;,
            &quot;text_score&quot;: 0.62,
            &quot;semantic_score&quot;: 0.89,
            &quot;score&quot;: 0.91,
            &quot;movement&quot;: 1
        }
    ],
    &quot;meta&quot;: {
        &quot;latency_ms&quot;: 212,
        &quot;embedding_model&quot;: &quot;gemini/gemini-embedding-001&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-knowledge-search" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-knowledge-search"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-knowledge-search"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-knowledge-search" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-knowledge-search">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-knowledge-search" data-method="POST"
      data-path="api/v1/knowledge/search"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-knowledge-search', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-knowledge-search"
                    onclick="tryItOut('POSTapi-v1-knowledge-search');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-knowledge-search"
                    onclick="cancelTryOut('POSTapi-v1-knowledge-search');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-knowledge-search"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/knowledge/search</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-knowledge-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-knowledge-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>query</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="query"                data-endpoint="POSTapi-v1-knowledge-search"
               value="o rastreio do meu pedido parou, o que faço?"
               data-component="body">
    <br>
<p>The question, as a customer would ask it. Must be at least 2 characters. Must not be greater than 500 characters. Example: <code>o rastreio do meu pedido parou, o que faço?</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>text_weight</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="text_weight"                data-endpoint="POSTapi-v1-knowledge-search"
               value="0.4"
               data-component="body">
    <br>
<p>Weight of the full-text score, 0 to 1 (the rest is semantic). Must be at least 0. Must not be greater than 1. Example: <code>0.4</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>top_k</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="top_k"                data-endpoint="POSTapi-v1-knowledge-search"
               value="5"
               data-component="body">
    <br>
<p>Number of passages (1-10). Must be at least 1. Must not be greater than 10. Example: <code>5</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>rerank</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-knowledge-search" style="display: none">
            <input type="radio" name="rerank"
                   value="true"
                   data-endpoint="POSTapi-v1-knowledge-search"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-knowledge-search" style="display: none">
            <input type="radio" name="rerank"
                   value="false"
                   data-endpoint="POSTapi-v1-knowledge-search"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Combine both rankings with reciprocal rank fusion. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category_id"                data-endpoint="POSTapi-v1-knowledge-search"
               value=""
               data-component="body">
    <br>
<p>Only articles of this category.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>customer_visible_only</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-knowledge-search" style="display: none">
            <input type="radio" name="customer_visible_only"
                   value="true"
                   data-endpoint="POSTapi-v1-knowledge-search"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-knowledge-search" style="display: none">
            <input type="radio" name="customer_visible_only"
                   value="false"
                   data-endpoint="POSTapi-v1-knowledge-search"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Only articles the AI may use in replies. Example: <code>false</code></p>
        </div>
        </form>

                    <h2 id="knowledge-base-POSTapi-v1-knowledge-articles-import">Import a Markdown file (draft)</h2>

<p>
</p>

<p>The first "# Heading" becomes the title (or the file name).</p>

<span id="example-requests-POSTapi-v1-knowledge-articles-import">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/knowledge/articles/import" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@/tmp/php8s4uou40ats48jkpFOa" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles/import"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-knowledge-articles-import">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5rkw9pfecy73z5y8rts&quot;,
        &quot;title&quot;: &quot;Eius et animi quos.&quot;,
        &quot;visibility&quot;: &quot;customers&quot;,
        &quot;tags&quot;: [],
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5rkw9pfecy73z5y8rtr&quot;,
            &quot;name&quot;: &quot;Nemo Voluptate&quot;,
            &quot;slug&quot;: &quot;nemo-voluptate&quot;,
            &quot;description&quot;: &quot;Ut et recusandae modi rerum ex repellendus.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;status&quot;: &quot;draft&quot;,
        &quot;has_unpublished_changes&quot;: false,
        &quot;published_at&quot;: null,
        &quot;index&quot;: {
            &quot;status&quot;: &quot;none&quot;,
            &quot;progress&quot;: 0,
            &quot;error&quot;: null,
            &quot;indexed_at&quot;: null,
            &quot;chunks_count&quot;: 0
        },
        &quot;citations_count&quot;: 0,
        &quot;updated_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5rkw9pfecy73z5y8rtq&quot;,
            &quot;name&quot;: &quot;Dr. Alan Green&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-knowledge-articles-import" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-knowledge-articles-import"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-knowledge-articles-import"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-knowledge-articles-import" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-knowledge-articles-import">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-knowledge-articles-import" data-method="POST"
      data-path="api/v1/knowledge/articles/import"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-knowledge-articles-import', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-knowledge-articles-import"
                    onclick="tryItOut('POSTapi-v1-knowledge-articles-import');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-knowledge-articles-import"
                    onclick="cancelTryOut('POSTapi-v1-knowledge-articles-import');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-knowledge-articles-import"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/knowledge/articles/import</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-knowledge-articles-import"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-knowledge-articles-import"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-knowledge-articles-import"
               value=""
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 200 kilobytes. Example: <code>/tmp/php8s4uou40ats48jkpFOa</code></p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>index</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>error</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Provider error code when indexing failed.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>indexed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Markdown working copy (single-article responses only).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>published_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>updated_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="knowledge-base-GETapi-v1-knowledge-articles">List articles</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-knowledge-articles">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/knowledge/articles?q=entrega&amp;status=published&amp;index_status=&amp;category_id=&amp;per_page=25&amp;page=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles"
);

const params = {
    "q": "entrega",
    "status": "published",
    "index_status": "",
    "category_id": "",
    "per_page": "25",
    "page": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-knowledge-articles">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;01m50gf5s2p3xjv09k7vg48fhc&quot;,
            &quot;title&quot;: &quot;Eius et animi quos.&quot;,
            &quot;visibility&quot;: &quot;customers&quot;,
            &quot;tags&quot;: [],
            &quot;category&quot;: {
                &quot;id&quot;: &quot;01m50gf5s2p3xjv09k7vg48fhb&quot;,
                &quot;name&quot;: &quot;Recusandae Modi&quot;,
                &quot;slug&quot;: &quot;recusandae-modi&quot;,
                &quot;description&quot;: &quot;Ex repellendus assumenda et tenetur ab reiciendis.&quot;,
                &quot;sla_targets&quot;: {
                    &quot;low&quot;: {
                        &quot;first_response_minutes&quot;: 480,
                        &quot;resolution_minutes&quot;: 4320,
                        &quot;is_custom&quot;: false
                    },
                    &quot;normal&quot;: {
                        &quot;first_response_minutes&quot;: 240,
                        &quot;resolution_minutes&quot;: 1440,
                        &quot;is_custom&quot;: false
                    },
                    &quot;high&quot;: {
                        &quot;first_response_minutes&quot;: 60,
                        &quot;resolution_minutes&quot;: 480,
                        &quot;is_custom&quot;: false
                    },
                    &quot;urgent&quot;: {
                        &quot;first_response_minutes&quot;: 15,
                        &quot;resolution_minutes&quot;: 240,
                        &quot;is_custom&quot;: false
                    }
                }
            },
            &quot;status&quot;: &quot;draft&quot;,
            &quot;has_unpublished_changes&quot;: false,
            &quot;published_at&quot;: null,
            &quot;index&quot;: {
                &quot;status&quot;: &quot;none&quot;,
                &quot;progress&quot;: 0,
                &quot;error&quot;: null,
                &quot;indexed_at&quot;: null,
                &quot;chunks_count&quot;: 0
            },
            &quot;citations_count&quot;: 0,
            &quot;updated_by&quot;: {
                &quot;id&quot;: &quot;01m50gf5s1c1x7tnca68taca7d&quot;,
                &quot;name&quot;: &quot;Nelson Little&quot;,
                &quot;role&quot;: &quot;customer&quot;,
                &quot;email&quot;: null
            },
            &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
            &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
        },
        {
            &quot;id&quot;: &quot;01m50gf5s6pd3pxjhajffamz6b&quot;,
            &quot;title&quot;: &quot;Perspiciatis deserunt ducimus corrupti et.&quot;,
            &quot;visibility&quot;: &quot;customers&quot;,
            &quot;tags&quot;: [],
            &quot;category&quot;: {
                &quot;id&quot;: &quot;01m50gf5s515xf0cmxh04p72j7&quot;,
                &quot;name&quot;: &quot;Ea Ut&quot;,
                &quot;slug&quot;: &quot;ea-ut&quot;,
                &quot;description&quot;: &quot;Deserunt sint quis in quod.&quot;,
                &quot;sla_targets&quot;: {
                    &quot;low&quot;: {
                        &quot;first_response_minutes&quot;: 480,
                        &quot;resolution_minutes&quot;: 4320,
                        &quot;is_custom&quot;: false
                    },
                    &quot;normal&quot;: {
                        &quot;first_response_minutes&quot;: 240,
                        &quot;resolution_minutes&quot;: 1440,
                        &quot;is_custom&quot;: false
                    },
                    &quot;high&quot;: {
                        &quot;first_response_minutes&quot;: 60,
                        &quot;resolution_minutes&quot;: 480,
                        &quot;is_custom&quot;: false
                    },
                    &quot;urgent&quot;: {
                        &quot;first_response_minutes&quot;: 15,
                        &quot;resolution_minutes&quot;: 240,
                        &quot;is_custom&quot;: false
                    }
                }
            },
            &quot;status&quot;: &quot;draft&quot;,
            &quot;has_unpublished_changes&quot;: false,
            &quot;published_at&quot;: null,
            &quot;index&quot;: {
                &quot;status&quot;: &quot;none&quot;,
                &quot;progress&quot;: 0,
                &quot;error&quot;: null,
                &quot;indexed_at&quot;: null,
                &quot;chunks_count&quot;: 0
            },
            &quot;citations_count&quot;: 0,
            &quot;updated_by&quot;: {
                &quot;id&quot;: &quot;01m50gf5s515xf0cmxh04p72j6&quot;,
                &quot;name&quot;: &quot;Dr. Lennie Klocko&quot;,
                &quot;role&quot;: &quot;customer&quot;,
                &quot;email&quot;: null
            },
            &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
            &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;page&quot;: 1,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-knowledge-articles" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-knowledge-articles"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-knowledge-articles"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-knowledge-articles" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-knowledge-articles">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-knowledge-articles" data-method="GET"
      data-path="api/v1/knowledge/articles"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-knowledge-articles', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-knowledge-articles"
                    onclick="tryItOut('GETapi-v1-knowledge-articles');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-knowledge-articles"
                    onclick="cancelTryOut('GETapi-v1-knowledge-articles');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-knowledge-articles"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/knowledge/articles</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-knowledge-articles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-knowledge-articles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-knowledge-articles"
               value="entrega"
               data-component="query">
    <br>
<p>Search titles. Must not be greater than 100 characters. Example: <code>entrega</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-knowledge-articles"
               value="published"
               data-component="query">
    <br>
<p>draft, published or archived. Example: <code>published</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>index_status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="index_status"                data-endpoint="GETapi-v1-knowledge-articles"
               value=""
               data-component="query">
    <br>
<p>none, queued, indexing, indexed or failed.</p>
Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category_id"                data-endpoint="GETapi-v1-knowledge-articles"
               value=""
               data-component="query">
    <br>
<p>Category ULID.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-knowledge-articles"
               value="25"
               data-component="query">
    <br>
<p>Page size (1-100). Must be at least 1. Must not be greater than 100. Example: <code>25</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-knowledge-articles"
               value="1"
               data-component="query">
    <br>
<p>Page number. Must be at least 1. Example: <code>1</code></p>
            </div>
                </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>index</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>error</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Provider error code when indexing failed.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>indexed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Markdown working copy (single-article responses only).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>published_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>updated_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="knowledge-base-POSTapi-v1-knowledge-articles">Create an article (draft)</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-knowledge-articles">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/knowledge/articles" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": \"Política de entregas e prazos\",
    \"body\": \"## Prazos\\n\\nSul e Sudeste: 3 a 5 dias úteis.\",
    \"visibility\": \"customers\",
    \"tags\": [
        \"b\"
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": "Política de entregas e prazos",
    "body": "## Prazos\n\nSul e Sudeste: 3 a 5 dias úteis.",
    "visibility": "customers",
    "tags": [
        "b"
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-knowledge-articles">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5sdpbyzad8psvcwmcby&quot;,
        &quot;title&quot;: &quot;Et animi quos velit.&quot;,
        &quot;visibility&quot;: &quot;customers&quot;,
        &quot;tags&quot;: [],
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5sc1zp7pyrgn023x2sq&quot;,
            &quot;name&quot;: &quot;Tenetur Ab&quot;,
            &quot;slug&quot;: &quot;tenetur-ab&quot;,
            &quot;description&quot;: &quot;Quia perspiciatis deserunt ducimus corrupti et.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;status&quot;: &quot;draft&quot;,
        &quot;has_unpublished_changes&quot;: false,
        &quot;published_at&quot;: null,
        &quot;index&quot;: {
            &quot;status&quot;: &quot;none&quot;,
            &quot;progress&quot;: 0,
            &quot;error&quot;: null,
            &quot;indexed_at&quot;: null,
            &quot;chunks_count&quot;: 0
        },
        &quot;citations_count&quot;: 0,
        &quot;updated_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5sc1zp7pyrgn023x2sp&quot;,
            &quot;name&quot;: &quot;Mr. Murl Cruickshank Jr.&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-knowledge-articles" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-knowledge-articles"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-knowledge-articles"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-knowledge-articles" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-knowledge-articles">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-knowledge-articles" data-method="POST"
      data-path="api/v1/knowledge/articles"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-knowledge-articles', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-knowledge-articles"
                    onclick="tryItOut('POSTapi-v1-knowledge-articles');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-knowledge-articles"
                    onclick="cancelTryOut('POSTapi-v1-knowledge-articles');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-knowledge-articles"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/knowledge/articles</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-knowledge-articles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-knowledge-articles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-v1-knowledge-articles"
               value="Política de entregas e prazos"
               data-component="body">
    <br>
<p>Article title. Must be at least 3 characters. Must not be greater than 200 characters. Example: <code>Política de entregas e prazos</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-knowledge-articles"
               value="## Prazos

Sul e Sudeste: 3 a 5 dias úteis."
               data-component="body">
    <br>
<p>Markdown. Only the published version is indexed and cited. Must not be greater than 100000 characters. Example: `## Prazos</p>
<p>Sul e Sudeste: 3 a 5 dias úteis.`</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="visibility"                data-endpoint="POSTapi-v1-knowledge-articles"
               value="customers"
               data-component="body">
    <br>
<p>customers (staff and customers) or internal (staff only, never used in replies). Example: <code>customers</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>tags</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tags[0]"                data-endpoint="POSTapi-v1-knowledge-articles"
               data-component="body">
        <input type="text" style="display: none"
               name="tags[1]"                data-endpoint="POSTapi-v1-knowledge-articles"
               data-component="body">
    <br>
<p>Must be at least 1 character. Must not be greater than 40 characters.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category_id"                data-endpoint="POSTapi-v1-knowledge-articles"
               value=""
               data-component="body">
    <br>
<p>Category ULID. Must match an existing stored value.</p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>index</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>error</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Provider error code when indexing failed.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>indexed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Markdown working copy (single-article responses only).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>published_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>updated_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="knowledge-base-GETapi-v1-knowledge-articles--id-">Show an article</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-knowledge-articles--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/knowledge/articles/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-knowledge-articles--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5sjdpf9dff4r42xshe1&quot;,
        &quot;title&quot;: &quot;Et animi quos velit.&quot;,
        &quot;visibility&quot;: &quot;customers&quot;,
        &quot;tags&quot;: [],
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5sjdpf9dff4r42xshe0&quot;,
            &quot;name&quot;: &quot;Quia Perspiciatis&quot;,
            &quot;slug&quot;: &quot;quia-perspiciatis&quot;,
            &quot;description&quot;: &quot;Ducimus corrupti et dolores quia maiores assumenda.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;status&quot;: &quot;draft&quot;,
        &quot;has_unpublished_changes&quot;: false,
        &quot;published_at&quot;: null,
        &quot;index&quot;: {
            &quot;status&quot;: &quot;none&quot;,
            &quot;progress&quot;: 0,
            &quot;error&quot;: null,
            &quot;indexed_at&quot;: null,
            &quot;chunks_count&quot;: 0
        },
        &quot;citations_count&quot;: 0,
        &quot;updated_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5sh6nks2gqmxcp2jjyq&quot;,
            &quot;name&quot;: &quot;Mr. Graham Crist&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-knowledge-articles--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-knowledge-articles--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-knowledge-articles--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-knowledge-articles--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-knowledge-articles--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-knowledge-articles--id-" data-method="GET"
      data-path="api/v1/knowledge/articles/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-knowledge-articles--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-knowledge-articles--id-"
                    onclick="tryItOut('GETapi-v1-knowledge-articles--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-knowledge-articles--id-"
                    onclick="cancelTryOut('GETapi-v1-knowledge-articles--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-knowledge-articles--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/knowledge/articles/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-knowledge-articles--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-knowledge-articles--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-v1-knowledge-articles--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the article. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>index</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>error</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Provider error code when indexing failed.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>indexed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Markdown working copy (single-article responses only).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>published_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>updated_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="knowledge-base-PUTapi-v1-knowledge-articles--id-">Edit an article</h2>

<p>
</p>

<p>Changes the working copy; a published article keeps serving its
published version until it is published again.</p>

<span id="example-requests-PUTapi-v1-knowledge-articles--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8090/api/v1/knowledge/articles/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": \"Política de entregas e prazos\",
    \"body\": \"## Prazos\\n\\nSul e Sudeste: 3 a 5 dias úteis.\",
    \"visibility\": \"customers\",
    \"tags\": [
        \"b\"
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": "Política de entregas e prazos",
    "body": "## Prazos\n\nSul e Sudeste: 3 a 5 dias úteis.",
    "visibility": "customers",
    "tags": [
        "b"
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-knowledge-articles--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5sxy6zbn8yc6wd417mx&quot;,
        &quot;title&quot;: &quot;Accusamus non facere consectetur.&quot;,
        &quot;visibility&quot;: &quot;customers&quot;,
        &quot;tags&quot;: [],
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5sw0693eshvb8e8m7nc&quot;,
            &quot;name&quot;: &quot;Consequatur Ab&quot;,
            &quot;slug&quot;: &quot;consequatur-ab&quot;,
            &quot;description&quot;: &quot;Quaerat autem tempora voluptatem porro dolores unde et.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;status&quot;: &quot;draft&quot;,
        &quot;has_unpublished_changes&quot;: false,
        &quot;published_at&quot;: null,
        &quot;index&quot;: {
            &quot;status&quot;: &quot;none&quot;,
            &quot;progress&quot;: 0,
            &quot;error&quot;: null,
            &quot;indexed_at&quot;: null,
            &quot;chunks_count&quot;: 0
        },
        &quot;citations_count&quot;: 0,
        &quot;updated_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5svg2083mvha32e6sx2&quot;,
            &quot;name&quot;: &quot;Abigail Kuhlman&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-knowledge-articles--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-knowledge-articles--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-knowledge-articles--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-knowledge-articles--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-knowledge-articles--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-knowledge-articles--id-" data-method="PUT"
      data-path="api/v1/knowledge/articles/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-knowledge-articles--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-knowledge-articles--id-"
                    onclick="tryItOut('PUTapi-v1-knowledge-articles--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-knowledge-articles--id-"
                    onclick="cancelTryOut('PUTapi-v1-knowledge-articles--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-knowledge-articles--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/knowledge/articles/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/knowledge/articles/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the article. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               value="Política de entregas e prazos"
               data-component="body">
    <br>
<p>Article title. Must be at least 3 characters. Must not be greater than 200 characters. Example: <code>Política de entregas e prazos</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               value="## Prazos

Sul e Sudeste: 3 a 5 dias úteis."
               data-component="body">
    <br>
<p>Markdown. Only the published version is indexed and cited. Must not be greater than 100000 characters. Example: `## Prazos</p>
<p>Sul e Sudeste: 3 a 5 dias úteis.`</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="visibility"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               value="customers"
               data-component="body">
    <br>
<p>customers (staff and customers) or internal (staff only, never used in replies). Example: <code>customers</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>tags</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tags[0]"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="tags[1]"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               data-component="body">
    <br>
<p>Must be at least 1 character. Must not be greater than 40 characters.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category_id"                data-endpoint="PUTapi-v1-knowledge-articles--id-"
               value=""
               data-component="body">
    <br>
<p>Category ULID. Must match an existing stored value.</p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>index</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>error</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Provider error code when indexing failed.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>indexed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Markdown working copy (single-article responses only).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>published_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>updated_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="knowledge-base-DELETEapi-v1-knowledge-articles--id-">Delete an article (admins)</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-knowledge-articles--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8090/api/v1/knowledge/articles/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-knowledge-articles--id-">
</span>
<span id="execution-results-DELETEapi-v1-knowledge-articles--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-knowledge-articles--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-knowledge-articles--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-knowledge-articles--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-knowledge-articles--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-knowledge-articles--id-" data-method="DELETE"
      data-path="api/v1/knowledge/articles/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-knowledge-articles--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-knowledge-articles--id-"
                    onclick="tryItOut('DELETEapi-v1-knowledge-articles--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-knowledge-articles--id-"
                    onclick="cancelTryOut('DELETEapi-v1-knowledge-articles--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-knowledge-articles--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/knowledge/articles/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-knowledge-articles--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-knowledge-articles--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-v1-knowledge-articles--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the article. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="knowledge-base-POSTapi-v1-knowledge-articles--article_id--publish">Publish an article</h2>

<p>
</p>

<p>Publishes the working copy and queues its indexing.</p>

<span id="example-requests-POSTapi-v1-knowledge-articles--article_id--publish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/knowledge/articles/architecto/publish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles/architecto/publish"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-knowledge-articles--article_id--publish">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5t8y50jgbherr3m8c2y&quot;,
        &quot;title&quot;: &quot;Et animi quos velit.&quot;,
        &quot;visibility&quot;: &quot;customers&quot;,
        &quot;tags&quot;: [],
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5t8y50jgbherr3m8c2x&quot;,
            &quot;name&quot;: &quot;Assumenda Odit&quot;,
            &quot;slug&quot;: &quot;assumenda-odit&quot;,
            &quot;description&quot;: &quot;Repellat officiis corporis nesciunt ut.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;status&quot;: &quot;draft&quot;,
        &quot;has_unpublished_changes&quot;: false,
        &quot;published_at&quot;: null,
        &quot;index&quot;: {
            &quot;status&quot;: &quot;none&quot;,
            &quot;progress&quot;: 0,
            &quot;error&quot;: null,
            &quot;indexed_at&quot;: null,
            &quot;chunks_count&quot;: 0
        },
        &quot;citations_count&quot;: 0,
        &quot;updated_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5t8y50jgbherr3m8c2w&quot;,
            &quot;name&quot;: &quot;Mona Lemke&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-knowledge-articles--article_id--publish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-knowledge-articles--article_id--publish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-knowledge-articles--article_id--publish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-knowledge-articles--article_id--publish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-knowledge-articles--article_id--publish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-knowledge-articles--article_id--publish" data-method="POST"
      data-path="api/v1/knowledge/articles/{article_id}/publish"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-knowledge-articles--article_id--publish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-knowledge-articles--article_id--publish"
                    onclick="tryItOut('POSTapi-v1-knowledge-articles--article_id--publish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-knowledge-articles--article_id--publish"
                    onclick="cancelTryOut('POSTapi-v1-knowledge-articles--article_id--publish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-knowledge-articles--article_id--publish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/knowledge/articles/{article_id}/publish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>article_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="article_id"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--publish"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the article. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>index</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>error</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Provider error code when indexing failed.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>indexed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Markdown working copy (single-article responses only).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>published_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>updated_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="knowledge-base-POSTapi-v1-knowledge-articles--article_id--unpublish">Unpublish an article</h2>

<p>
</p>

<p>The AI stops citing it immediately.</p>

<span id="example-requests-POSTapi-v1-knowledge-articles--article_id--unpublish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/knowledge/articles/architecto/unpublish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles/architecto/unpublish"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-knowledge-articles--article_id--unpublish">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5tfpa3dn8137tbr51wr&quot;,
        &quot;title&quot;: &quot;Et animi quos velit.&quot;,
        &quot;visibility&quot;: &quot;customers&quot;,
        &quot;tags&quot;: [],
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5tfpa3dn8137tbr51wq&quot;,
            &quot;name&quot;: &quot;Molestiae Ut&quot;,
            &quot;slug&quot;: &quot;molestiae-ut&quot;,
            &quot;description&quot;: &quot;Est esse sint aut.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;status&quot;: &quot;draft&quot;,
        &quot;has_unpublished_changes&quot;: false,
        &quot;published_at&quot;: null,
        &quot;index&quot;: {
            &quot;status&quot;: &quot;none&quot;,
            &quot;progress&quot;: 0,
            &quot;error&quot;: null,
            &quot;indexed_at&quot;: null,
            &quot;chunks_count&quot;: 0
        },
        &quot;citations_count&quot;: 0,
        &quot;updated_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5te3r0e3qtpb1yrdnh3&quot;,
            &quot;name&quot;: &quot;Alexa Hamill DDS&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-knowledge-articles--article_id--unpublish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-knowledge-articles--article_id--unpublish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-knowledge-articles--article_id--unpublish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-knowledge-articles--article_id--unpublish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-knowledge-articles--article_id--unpublish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-knowledge-articles--article_id--unpublish" data-method="POST"
      data-path="api/v1/knowledge/articles/{article_id}/unpublish"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-knowledge-articles--article_id--unpublish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-knowledge-articles--article_id--unpublish"
                    onclick="tryItOut('POSTapi-v1-knowledge-articles--article_id--unpublish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-knowledge-articles--article_id--unpublish"
                    onclick="cancelTryOut('POSTapi-v1-knowledge-articles--article_id--unpublish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-knowledge-articles--article_id--unpublish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/knowledge/articles/{article_id}/unpublish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--unpublish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--unpublish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>article_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="article_id"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--unpublish"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the article. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>index</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>error</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Provider error code when indexing failed.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>indexed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Markdown working copy (single-article responses only).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>published_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>updated_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="knowledge-base-POSTapi-v1-knowledge-articles--article_id--reindex">Index again</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-knowledge-articles--article_id--reindex">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/knowledge/articles/architecto/reindex" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles/architecto/reindex"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-knowledge-articles--article_id--reindex">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5tn2z65xbdrt121jfde&quot;,
        &quot;title&quot;: &quot;Et animi quos velit.&quot;,
        &quot;visibility&quot;: &quot;customers&quot;,
        &quot;tags&quot;: [],
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5tn2z65xbdrt121jfdd&quot;,
            &quot;name&quot;: &quot;Sint Aut&quot;,
            &quot;slug&quot;: &quot;sint-aut&quot;,
            &quot;description&quot;: &quot;Sunt suscipit doloribus fugiat ut aut deserunt et.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;status&quot;: &quot;draft&quot;,
        &quot;has_unpublished_changes&quot;: false,
        &quot;published_at&quot;: null,
        &quot;index&quot;: {
            &quot;status&quot;: &quot;none&quot;,
            &quot;progress&quot;: 0,
            &quot;error&quot;: null,
            &quot;indexed_at&quot;: null,
            &quot;chunks_count&quot;: 0
        },
        &quot;citations_count&quot;: 0,
        &quot;updated_by&quot;: {
            &quot;id&quot;: &quot;01m50gf5tmg7m4rwbcdw7ckmh1&quot;,
            &quot;name&quot;: &quot;Fae Bernhard Sr.&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-knowledge-articles--article_id--reindex" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-knowledge-articles--article_id--reindex"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-knowledge-articles--article_id--reindex"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-knowledge-articles--article_id--reindex" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-knowledge-articles--article_id--reindex">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-knowledge-articles--article_id--reindex" data-method="POST"
      data-path="api/v1/knowledge/articles/{article_id}/reindex"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-knowledge-articles--article_id--reindex', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-knowledge-articles--article_id--reindex"
                    onclick="tryItOut('POSTapi-v1-knowledge-articles--article_id--reindex');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-knowledge-articles--article_id--reindex"
                    onclick="cancelTryOut('POSTapi-v1-knowledge-articles--article_id--reindex');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-knowledge-articles--article_id--reindex"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/knowledge/articles/{article_id}/reindex</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--reindex"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--reindex"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>article_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="article_id"                data-endpoint="POSTapi-v1-knowledge-articles--article_id--reindex"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the article. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>draft</code></li> <li><code>published</code></li> <li><code>archived</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>visibility</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customers</code></li> <li><code>internal</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>index</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>queued</code></li> <li><code>indexing</code></li> <li><code>indexed</code></li> <li><code>failed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>error</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Provider error code when indexing failed.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>indexed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Markdown working copy (single-article responses only).</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>published_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>updated_by</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="knowledge-base-GETapi-v1-knowledge-articles--article_id--chunks">Indexed passages (&quot;how the AI sees it&quot;)</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-knowledge-articles--article_id--chunks">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/knowledge/articles/architecto/chunks" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/knowledge/articles/architecto/chunks"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-knowledge-articles--article_id--chunks">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;01J...&quot;,
            &quot;ordinal&quot;: 1,
            &quot;heading&quot;: &quot;Prazos por regi&atilde;o&quot;,
            &quot;content&quot;: &quot;Sul e Sudeste: 3 a 5 dias &uacute;teis.&quot;,
            &quot;tokens&quot;: 174,
            &quot;embedding_model&quot;: &quot;gemini/gemini-embedding-001&quot;,
            &quot;citations_count&quot;: 37
        }
    ],
    &quot;meta&quot;: {
        &quot;embedding_model&quot;: &quot;gemini/gemini-embedding-001&quot;,
        &quot;target_tokens&quot;: 320,
        &quot;overlap_tokens&quot;: 40
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-knowledge-articles--article_id--chunks" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-knowledge-articles--article_id--chunks"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-knowledge-articles--article_id--chunks"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-knowledge-articles--article_id--chunks" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-knowledge-articles--article_id--chunks">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-knowledge-articles--article_id--chunks" data-method="GET"
      data-path="api/v1/knowledge/articles/{article_id}/chunks"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-knowledge-articles--article_id--chunks', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-knowledge-articles--article_id--chunks"
                    onclick="tryItOut('GETapi-v1-knowledge-articles--article_id--chunks');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-knowledge-articles--article_id--chunks"
                    onclick="cancelTryOut('GETapi-v1-knowledge-articles--article_id--chunks');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-knowledge-articles--article_id--chunks"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/knowledge/articles/{article_id}/chunks</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-knowledge-articles--article_id--chunks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-knowledge-articles--article_id--chunks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>article_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="article_id"                data-endpoint="GETapi-v1-knowledge-articles--article_id--chunks"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the article. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="metrics">Metrics</h1>

    <p>Dashboard figures for admins, always limited to the current tenant.</p>

                                <h2 id="metrics-GETapi-v1-metrics">Dashboard metrics</h2>

<p>
</p>

<p>Ticket volume, median time to first staff reply and first-response SLA
compliance for the period, each compared with the preceding period of
the same length.</p>

<span id="example-requests-GETapi-v1-metrics">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/metrics?from=2026-09-13&amp;to=2026-10-12" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/metrics"
);

const params = {
    "from": "2026-09-13",
    "to": "2026-10-12",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-metrics">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;period&quot;: {
            &quot;from&quot;: &quot;2026-09-13&quot;,
            &quot;to&quot;: &quot;2026-10-12&quot;
        },
        &quot;tickets_received&quot;: {
            &quot;value&quot;: 1412,
            &quot;previous&quot;: 1307
        },
        &quot;first_response_median_minutes&quot;: {
            &quot;value&quot;: 14.5,
            &quot;previous&quot;: 20.5
        },
        &quot;sla_first_response_percent&quot;: {
            &quot;value&quot;: 93.4,
            &quot;previous&quot;: 91.3
        },
        &quot;volume_by_day&quot;: [
            {
                &quot;date&quot;: &quot;2026-10-12&quot;,
                &quot;count&quot;: 42
            }
        ],
        &quot;by_category&quot;: [
            {
                &quot;category_id&quot;: &quot;01JABCDEF0123456789ABCDEFG&quot;,
                &quot;name&quot;: &quot;Hardware&quot;,
                &quot;count&quot;: 486
            }
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-metrics" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-metrics"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-metrics"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-metrics" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-metrics">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-metrics" data-method="GET"
      data-path="api/v1/metrics"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-metrics', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-metrics"
                    onclick="tryItOut('GETapi-v1-metrics');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-metrics"
                    onclick="cancelTryOut('GETapi-v1-metrics');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-metrics"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/metrics</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-metrics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-metrics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="from"                data-endpoint="GETapi-v1-metrics"
               value="2026-09-13"
               data-component="query">
    <br>
<p>First day (Y-m-d). Defaults to 30 days before <code>to</code>; at most one year before it. Must be a valid date in the format <code>Y-m-d</code>. Example: <code>2026-09-13</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="to"                data-endpoint="GETapi-v1-metrics"
               value="2026-10-12"
               data-component="query">
    <br>
<p>Last day (Y-m-d), not in the future. Defaults to today. Must be a valid date in the format <code>Y-m-d</code>. Must be a date after or equal to <code>from</code>. Must be a date before or equal to <code>today</code>. Example: <code>2026-10-12</code></p>
            </div>
                </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>first_response_median_minutes</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>value</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Null when no ticket was answered.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>previous</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>sla_first_response_percent</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>value</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Null when no deadline is settled yet.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>previous</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>by_category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Null for tickets without a category.</p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                    <h1 id="push-notifications">Push notifications</h1>

    <p>Devices that receive push notifications (mobile app, bearer tokens only).</p>

                                <h2 id="push-notifications-POSTapi-v1-push-devices">Register this device</h2>

<p>
</p>

<p>Saves the Expo push token of the phone using the current API token.
Revoking the API token (signing out) unregisters the device too.</p>

<span id="example-requests-POSTapi-v1-push-devices">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/push-devices" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"token\": \"ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]\",
    \"platform\": \"android\",
    \"locale\": \"pt-BR\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/push-devices"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]",
    "platform": "android",
    "locale": "pt-BR"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-push-devices">
</span>
<span id="execution-results-POSTapi-v1-push-devices" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-push-devices"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-push-devices"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-push-devices" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-push-devices">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-push-devices" data-method="POST"
      data-path="api/v1/push-devices"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-push-devices', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-push-devices"
                    onclick="tryItOut('POSTapi-v1-push-devices');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-push-devices"
                    onclick="cancelTryOut('POSTapi-v1-push-devices');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-push-devices"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/push-devices</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-push-devices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-push-devices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="token"                data-endpoint="POSTapi-v1-push-devices"
               value="ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
               data-component="body">
    <br>
<p>Expo push token of the device. Must match the regex /^Expo(nent)?PushToken[[A-Za-z0-9_-]{1,200}]$/. Must not be greater than 255 characters. Example: <code>ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>platform</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="platform"                data-endpoint="POSTapi-v1-push-devices"
               value="android"
               data-component="body">
    <br>
<p>android or ios. Example: <code>android</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>android</code></li> <li><code>ios</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>locale</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="locale"                data-endpoint="POSTapi-v1-push-devices"
               value="pt-BR"
               data-component="body">
    <br>
<p>Language of the notifications: pt-BR or en. Example: <code>pt-BR</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>pt-BR</code></li> <li><code>en</code></li></ul>
        </div>
        </form>

                    <h2 id="push-notifications-DELETEapi-v1-push-devices-current">Unregister this device</h2>

<p>
</p>

<p>Stops push notifications to the phone using the current API token.</p>

<span id="example-requests-DELETEapi-v1-push-devices-current">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8090/api/v1/push-devices/current" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/push-devices/current"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-push-devices-current">
</span>
<span id="execution-results-DELETEapi-v1-push-devices-current" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-push-devices-current"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-push-devices-current"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-push-devices-current" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-push-devices-current">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-push-devices-current" data-method="DELETE"
      data-path="api/v1/push-devices/current"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-push-devices-current', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-push-devices-current"
                    onclick="tryItOut('DELETEapi-v1-push-devices-current');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-push-devices-current"
                    onclick="cancelTryOut('DELETEapi-v1-push-devices-current');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-push-devices-current"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/push-devices/current</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-push-devices-current"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-push-devices-current"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="realtime">Realtime</h1>

    <p>WebSocket (Pusher protocol, Laravel Reverb) on the API origin under /app. Subscribe to private channels after authorizing them with POST /api/broadcasting/auth.</p>

                                <h2 id="realtime-GETapi-v1-realtime">Realtime settings</h2>

<p>
</p>

<p>Public app key of the WebSocket server, so clients need no build-time
configuration. The key is not a secret; channel access is authorized
per subscription.</p>

<span id="example-requests-GETapi-v1-realtime">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/realtime" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/realtime"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-realtime">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;enabled&quot;: true,
        &quot;key&quot;: &quot;ticko-app-key&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-realtime" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-realtime"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-realtime"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-realtime" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-realtime">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-realtime" data-method="GET"
      data-path="api/v1/realtime"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-realtime', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-realtime"
                    onclick="tryItOut('GETapi-v1-realtime');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-realtime"
                    onclick="cancelTryOut('GETapi-v1-realtime');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-realtime"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/realtime</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-realtime"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-realtime"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="team">Team</h1>

    <p>Members of the tenant. Staff can list them; only admins create or change them.</p>

                                <h2 id="team-GETapi-v1-team">List members</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-team">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/team?role=agent&amp;q=ana&amp;page=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/team"
);

const params = {
    "role": "agent",
    "q": "ana",
    "page": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-team">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;01m50gf5vzh1n8xvzsna09c6t5&quot;,
            &quot;name&quot;: &quot;Ms. Audra Crooks II&quot;,
            &quot;email&quot;: &quot;gilbert32@example.com&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;is_active&quot;: true,
            &quot;notification_preferences&quot;: {
                &quot;push_replies&quot;: true,
                &quot;push_status_changes&quot;: true
            },
            &quot;open_tickets_count&quot;: 1,
            &quot;created_at&quot;: &quot;2026-10-15T19:26:19+00:00&quot;
        },
        {
            &quot;id&quot;: &quot;01m50gf5w2bq550xwnjsggss9m&quot;,
            &quot;name&quot;: &quot;Emanuel Emmerich&quot;,
            &quot;email&quot;: &quot;elton.conroy@example.com&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;is_active&quot;: true,
            &quot;notification_preferences&quot;: {
                &quot;push_replies&quot;: true,
                &quot;push_status_changes&quot;: true
            },
            &quot;open_tickets_count&quot;: 1,
            &quot;created_at&quot;: &quot;2026-10-15T19:26:19+00:00&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;page&quot;: 1,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 50,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-team" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-team"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-team"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-team" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-team">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-team" data-method="GET"
      data-path="api/v1/team"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-team', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-team"
                    onclick="tryItOut('GETapi-v1-team');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-team"
                    onclick="cancelTryOut('GETapi-v1-team');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-team"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/team</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-team"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-team"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="role"                data-endpoint="GETapi-v1-team"
               value="agent"
               data-component="query">
    <br>
<p>Filter by role: customer, agent or admin. Example: <code>agent</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-team"
               value="ana"
               data-component="query">
    <br>
<p>Search name and e-mail. Must not be greater than 100 characters. Example: <code>ana</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-team"
               value="1"
               data-component="query">
    <br>
<p>Page number. Must be at least 1. Example: <code>1</code></p>
            </div>
                </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tenant</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the sign-in endpoints and /auth/me.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>open_tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Open tickets assigned to the member (team listing only).</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="team-POSTapi-v1-team">Add a member</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-team">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/team" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Ana Souza\",
    \"email\": \"ana@acme.test\",
    \"password\": \"a-long-unique-passphrase\",
    \"role\": \"agent\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/team"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Ana Souza",
    "email": "ana@acme.test",
    "password": "a-long-unique-passphrase",
    "role": "agent"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-team">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5w718ymyr6mcfp9cwx9&quot;,
        &quot;name&quot;: &quot;Ms. Audra Crooks II&quot;,
        &quot;email&quot;: &quot;hirthe.theo@example.com&quot;,
        &quot;role&quot;: &quot;customer&quot;,
        &quot;is_active&quot;: true,
        &quot;notification_preferences&quot;: {
            &quot;push_replies&quot;: true,
            &quot;push_status_changes&quot;: true
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:19+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-team" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-team"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-team"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-team" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-team">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-team" data-method="POST"
      data-path="api/v1/team"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-team', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-team"
                    onclick="tryItOut('POSTapi-v1-team');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-team"
                    onclick="cancelTryOut('POSTapi-v1-team');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-team"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/team</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-team"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-team"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-team"
               value="Ana Souza"
               data-component="body">
    <br>
<p>Full name. Must not be greater than 120 characters. Example: <code>Ana Souza</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-team"
               value="ana@acme.test"
               data-component="body">
    <br>
<p>E-mail, unique within the tenant. Must be a valid email address. Must not be greater than 254 characters. Example: <code>ana@acme.test</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-team"
               value="a-long-unique-passphrase"
               data-component="body">
    <br>
<p>Initial password (min 12 chars, not found in known breaches). Example: <code>a-long-unique-passphrase</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="role"                data-endpoint="POSTapi-v1-team"
               value="agent"
               data-component="body">
    <br>
<p>customer, agent or admin. Example: <code>agent</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tenant</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the sign-in endpoints and /auth/me.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>open_tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Open tickets assigned to the member (team listing only).</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="team-GETapi-v1-team--member_id-">Show a member</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-team--member_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/team/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/team/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-team--member_id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5w9e6ppz51ms43ph8s6&quot;,
        &quot;name&quot;: &quot;Ms. Elisabeth Okuneva&quot;,
        &quot;email&quot;: &quot;leo34@example.net&quot;,
        &quot;role&quot;: &quot;customer&quot;,
        &quot;is_active&quot;: true,
        &quot;notification_preferences&quot;: {
            &quot;push_replies&quot;: true,
            &quot;push_status_changes&quot;: true
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:19+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-team--member_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-team--member_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-team--member_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-team--member_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-team--member_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-team--member_id-" data-method="GET"
      data-path="api/v1/team/{member_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-team--member_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-team--member_id-"
                    onclick="tryItOut('GETapi-v1-team--member_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-team--member_id-"
                    onclick="cancelTryOut('GETapi-v1-team--member_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-team--member_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/team/{member_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-team--member_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-team--member_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>member_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="member_id"                data-endpoint="GETapi-v1-team--member_id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the member. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tenant</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the sign-in endpoints and /auth/me.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>open_tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Open tickets assigned to the member (team listing only).</p>
                    </div>
                                    </details>
        </div>
                        <h2 id="team-PATCHapi-v1-team--member_id-">Update a member</h2>

<p>
</p>

<p>Admins cannot change their own role or deactivate themselves.</p>

<span id="example-requests-PATCHapi-v1-team--member_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8090/api/v1/team/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Ana Souza\",
    \"role\": \"admin\",
    \"is_active\": true
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/team/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Ana Souza",
    "role": "admin",
    "is_active": true
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-team--member_id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5wcaqpfjt2k1hy7g5q7&quot;,
        &quot;name&quot;: &quot;Ms. Audra Crooks II&quot;,
        &quot;email&quot;: &quot;pfritsch@example.com&quot;,
        &quot;role&quot;: &quot;customer&quot;,
        &quot;is_active&quot;: true,
        &quot;notification_preferences&quot;: {
            &quot;push_replies&quot;: true,
            &quot;push_status_changes&quot;: true
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:19+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PATCHapi-v1-team--member_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-team--member_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-team--member_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-team--member_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-team--member_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-team--member_id-" data-method="PATCH"
      data-path="api/v1/team/{member_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-team--member_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-team--member_id-"
                    onclick="tryItOut('PATCHapi-v1-team--member_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-team--member_id-"
                    onclick="cancelTryOut('PATCHapi-v1-team--member_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-team--member_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/team/{member_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-team--member_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-team--member_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>member_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="member_id"                data-endpoint="PATCHapi-v1-team--member_id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the member. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PATCHapi-v1-team--member_id-"
               value="Ana Souza"
               data-component="body">
    <br>
<p>Full name. Must not be greater than 120 characters. Example: <code>Ana Souza</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="role"                data-endpoint="PATCHapi-v1-team--member_id-"
               value="admin"
               data-component="body">
    <br>
<p>customer, agent or admin. Example: <code>admin</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_active</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-team--member_id-" style="display: none">
            <input type="radio" name="is_active"
                   value="true"
                   data-endpoint="PATCHapi-v1-team--member_id-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-team--member_id-" style="display: none">
            <input type="radio" name="is_active"
                   value="false"
                   data-endpoint="PATCHapi-v1-team--member_id-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Deactivated users cannot sign in; their tokens are revoked. Example: <code>true</code></p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>tenant</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Sent by the sign-in endpoints and /auth/me.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>open_tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Open tickets assigned to the member (team listing only).</p>
                    </div>
                                    </details>
        </div>
                    <h1 id="ticket-messages">Ticket messages</h1>

    <p>Conversation on a ticket. Internal notes are only visible to staff.</p>

                                <h2 id="ticket-messages-GETapi-v1-tickets--ticket_id--messages">List messages</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-tickets--ticket_id--messages">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/tickets/architecto/messages?page=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto/messages"
);

const params = {
    "page": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-tickets--ticket_id--messages">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;01m50gf5p3deghj0zc43rkyv1e&quot;,
            &quot;ticket_id&quot;: &quot;01m50gf5nz4c6j1nv3rqy4q10b&quot;,
            &quot;body&quot;: &quot;Veritatis ipsa reprehenderit quos nihil eos non. Sunt voluptate non voluptas. Voluptatem et quibusdam ea dolores laboriosam nihil. Facilis dolorum et eum deserunt adipisci vel.&quot;,
            &quot;is_internal&quot;: false,
            &quot;author&quot;: {
                &quot;id&quot;: &quot;01m50gf5p1v1yw0agk04w7mkd0&quot;,
                &quot;name&quot;: &quot;Kieran Torp III&quot;,
                &quot;role&quot;: &quot;customer&quot;,
                &quot;email&quot;: null
            },
            &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
        },
        {
            &quot;id&quot;: &quot;01m50gf5pbkxbfzgneezyxatg9&quot;,
            &quot;ticket_id&quot;: &quot;01m50gf5p90pa906jcqrhap577&quot;,
            &quot;body&quot;: &quot;Perspiciatis quia omnis molestias dolor veniam. Doloribus adipisci accusamus voluptas cumque exercitationem ipsum.&quot;,
            &quot;is_internal&quot;: false,
            &quot;author&quot;: {
                &quot;id&quot;: &quot;01m50gf5pafq0h8w58k8p6f7a4&quot;,
                &quot;name&quot;: &quot;Leonard Eichmann I&quot;,
                &quot;role&quot;: &quot;customer&quot;,
                &quot;email&quot;: null
            },
            &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;page&quot;: 1,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 50,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-tickets--ticket_id--messages" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-tickets--ticket_id--messages"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-tickets--ticket_id--messages"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-tickets--ticket_id--messages" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-tickets--ticket_id--messages">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-tickets--ticket_id--messages" data-method="GET"
      data-path="api/v1/tickets/{ticket_id}/messages"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-tickets--ticket_id--messages', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-tickets--ticket_id--messages"
                    onclick="tryItOut('GETapi-v1-tickets--ticket_id--messages');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-tickets--ticket_id--messages"
                    onclick="cancelTryOut('GETapi-v1-tickets--ticket_id--messages');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-tickets--ticket_id--messages"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/tickets/{ticket_id}/messages</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-tickets--ticket_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-tickets--ticket_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_id"                data-endpoint="GETapi-v1-tickets--ticket_id--messages"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-tickets--ticket_id--messages"
               value="1"
               data-component="query">
    <br>
<p>Page number (50 messages per page, oldest first). Example: <code>1</code></p>
            </div>
                </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>author</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="ticket-messages-POSTapi-v1-tickets--ticket_id--messages">Post a message</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-tickets--ticket_id--messages">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/tickets/architecto/messages" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"body\": \"Could you try restarting it?\",
    \"is_internal\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto/messages"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "body": "Could you try restarting it?",
    "is_internal": false
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-tickets--ticket_id--messages">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5pn7669pkvz26ns3pxq&quot;,
        &quot;ticket_id&quot;: &quot;01m50gf5pkmyfqtn48phnnzdns&quot;,
        &quot;body&quot;: &quot;Et animi quos velit et fugiat. Nihil accusantium harum mollitia modi deserunt. Ab provident perspiciatis quo omnis nostrum aut adipisci.&quot;,
        &quot;is_internal&quot;: false,
        &quot;author&quot;: {
            &quot;id&quot;: &quot;01m50gf5pmfg3yjacm6bb74whb&quot;,
            &quot;name&quot;: &quot;Mr. Madisen Schaden&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-tickets--ticket_id--messages" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-tickets--ticket_id--messages"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-tickets--ticket_id--messages"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-tickets--ticket_id--messages" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-tickets--ticket_id--messages">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-tickets--ticket_id--messages" data-method="POST"
      data-path="api/v1/tickets/{ticket_id}/messages"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-tickets--ticket_id--messages', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-tickets--ticket_id--messages"
                    onclick="tryItOut('POSTapi-v1-tickets--ticket_id--messages');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-tickets--ticket_id--messages"
                    onclick="cancelTryOut('POSTapi-v1-tickets--ticket_id--messages');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-tickets--ticket_id--messages"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/tickets/{ticket_id}/messages</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-tickets--ticket_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-tickets--ticket_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_id"                data-endpoint="POSTapi-v1-tickets--ticket_id--messages"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-tickets--ticket_id--messages"
               value="Could you try restarting it?"
               data-component="body">
    <br>
<p>Message text. Must not be greater than 20000 characters. Example: <code>Could you try restarting it?</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_internal</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-tickets--ticket_id--messages" style="display: none">
            <input type="radio" name="is_internal"
                   value="true"
                   data-endpoint="POSTapi-v1-tickets--ticket_id--messages"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-tickets--ticket_id--messages" style="display: none">
            <input type="radio" name="is_internal"
                   value="false"
                   data-endpoint="POSTapi-v1-tickets--ticket_id--messages"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Internal note visible to staff only. Example: <code>false</code></p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>author</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                    <h1 id="tickets">Tickets</h1>

    <p>Customers see and manage only their own tickets; agents and admins see every ticket of the tenant.</p>

                                <h2 id="tickets-GETapi-v1-tickets">List tickets</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-tickets">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/tickets?status=open&amp;state=active&amp;priority=high&amp;category_id=&amp;assignee_id=&amp;requester_id=&amp;unassigned=&amp;overdue=&amp;q=printer&amp;sort=-created_at&amp;per_page=25&amp;page=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets"
);

const params = {
    "status": "open",
    "state": "active",
    "priority": "high",
    "category_id": "",
    "assignee_id": "",
    "requester_id": "",
    "unassigned": "0",
    "overdue": "0",
    "q": "printer",
    "sort": "-created_at",
    "per_page": "25",
    "page": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-tickets">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;01m50gf5k6g361cwmgww24a9vx&quot;,
            &quot;subject&quot;: &quot;Eius et animi quos velit et.&quot;,
            &quot;description&quot;: &quot;Nihil accusantium harum mollitia modi deserunt. Ab provident perspiciatis quo omnis nostrum aut adipisci. Nostrum qui commodi incidunt iure.&quot;,
            &quot;status&quot;: &quot;open&quot;,
            &quot;priority&quot;: &quot;urgent&quot;,
            &quot;category&quot;: {
                &quot;id&quot;: &quot;01m50gf5k44mrd94z4hev33n33&quot;,
                &quot;name&quot;: &quot;Voluptatem Laboriosam&quot;,
                &quot;slug&quot;: &quot;voluptatem-laboriosam&quot;,
                &quot;description&quot;: &quot;Quis adipisci molestias fugit deleniti distinctio eum.&quot;,
                &quot;sla_targets&quot;: {
                    &quot;low&quot;: {
                        &quot;first_response_minutes&quot;: 480,
                        &quot;resolution_minutes&quot;: 4320,
                        &quot;is_custom&quot;: false
                    },
                    &quot;normal&quot;: {
                        &quot;first_response_minutes&quot;: 240,
                        &quot;resolution_minutes&quot;: 1440,
                        &quot;is_custom&quot;: false
                    },
                    &quot;high&quot;: {
                        &quot;first_response_minutes&quot;: 60,
                        &quot;resolution_minutes&quot;: 480,
                        &quot;is_custom&quot;: false
                    },
                    &quot;urgent&quot;: {
                        &quot;first_response_minutes&quot;: 15,
                        &quot;resolution_minutes&quot;: 240,
                        &quot;is_custom&quot;: false
                    }
                }
            },
            &quot;requester&quot;: {
                &quot;id&quot;: &quot;01m50gf5k2eax3xyr37k4vsda5&quot;,
                &quot;name&quot;: &quot;Mina Bauch&quot;,
                &quot;role&quot;: &quot;customer&quot;,
                &quot;email&quot;: null
            },
            &quot;assignee&quot;: {
                &quot;id&quot;: &quot;01m50gf5k3swjndsvkpgmamq8k&quot;,
                &quot;name&quot;: &quot;Donny Leffler&quot;,
                &quot;role&quot;: &quot;customer&quot;,
                &quot;email&quot;: null
            },
            &quot;last_message&quot;: {
                &quot;id&quot;: &quot;01m50gf5kb35pd40hw5w3cxpqh&quot;,
                &quot;excerpt&quot;: &quot;Aut libero aliquam veniam corporis. Mollitia deleniti nemo odit quia officia. Dignissimos neque blanditiis odio.&quot;,
                &quot;author_name&quot;: &quot;Prof. Daniella Emmerich III&quot;,
                &quot;author_role&quot;: &quot;customer&quot;,
                &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
            },
            &quot;resolved_at&quot;: null,
            &quot;closed_at&quot;: null,
            &quot;sla&quot;: {
                &quot;first_response_at&quot;: null,
                &quot;first_response_due_at&quot;: &quot;2026-10-15T19:41:18+00:00&quot;,
                &quot;resolution_due_at&quot;: &quot;2026-10-15T23:26:18+00:00&quot;,
                &quot;is_overdue&quot;: false
            },
            &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
            &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
        },
        {
            &quot;id&quot;: &quot;01m50gf5krgpb1g0afxm5e82da&quot;,
            &quot;subject&quot;: &quot;Ratione nemo voluptate accusamus ut et recusandae modi rerum.&quot;,
            &quot;description&quot;: &quot;Assumenda et tenetur ab reiciendis quia. Deserunt ducimus corrupti et dolores quia maiores. Odit doloribus repellat officiis corporis nesciunt ut ratione iure.&quot;,
            &quot;status&quot;: &quot;open&quot;,
            &quot;priority&quot;: &quot;urgent&quot;,
            &quot;category&quot;: {
                &quot;id&quot;: &quot;01m50gf5krgpb1g0afxm5e82d9&quot;,
                &quot;name&quot;: &quot;Et Ipsam&quot;,
                &quot;slug&quot;: &quot;et-ipsam&quot;,
                &quot;description&quot;: &quot;Et ut dicta vitae assumenda consequatur.&quot;,
                &quot;sla_targets&quot;: {
                    &quot;low&quot;: {
                        &quot;first_response_minutes&quot;: 480,
                        &quot;resolution_minutes&quot;: 4320,
                        &quot;is_custom&quot;: false
                    },
                    &quot;normal&quot;: {
                        &quot;first_response_minutes&quot;: 240,
                        &quot;resolution_minutes&quot;: 1440,
                        &quot;is_custom&quot;: false
                    },
                    &quot;high&quot;: {
                        &quot;first_response_minutes&quot;: 60,
                        &quot;resolution_minutes&quot;: 480,
                        &quot;is_custom&quot;: false
                    },
                    &quot;urgent&quot;: {
                        &quot;first_response_minutes&quot;: 15,
                        &quot;resolution_minutes&quot;: 240,
                        &quot;is_custom&quot;: false
                    }
                }
            },
            &quot;requester&quot;: {
                &quot;id&quot;: &quot;01m50gf5kpd7w11eea1xs7qzqm&quot;,
                &quot;name&quot;: &quot;Mr. Thaddeus Glover IV&quot;,
                &quot;role&quot;: &quot;customer&quot;,
                &quot;email&quot;: null
            },
            &quot;assignee&quot;: {
                &quot;id&quot;: &quot;01m50gf5kqafdv814nhjbnqd9e&quot;,
                &quot;name&quot;: &quot;Lucie Bauch MD&quot;,
                &quot;role&quot;: &quot;customer&quot;,
                &quot;email&quot;: null
            },
            &quot;last_message&quot;: {
                &quot;id&quot;: &quot;01m50gf5kwtsmsrxmb6hv81gmq&quot;,
                &quot;excerpt&quot;: &quot;Sunt quisquam sit repellendus. Eaque alias ratione dolores sed rem ea ut. Deserunt sint quis in quod.&quot;,
                &quot;author_name&quot;: &quot;Cordia Cummings&quot;,
                &quot;author_role&quot;: &quot;customer&quot;,
                &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
            },
            &quot;resolved_at&quot;: null,
            &quot;closed_at&quot;: null,
            &quot;sla&quot;: {
                &quot;first_response_at&quot;: null,
                &quot;first_response_due_at&quot;: &quot;2026-10-15T19:41:18+00:00&quot;,
                &quot;resolution_due_at&quot;: &quot;2026-10-15T23:26:18+00:00&quot;,
                &quot;is_overdue&quot;: false
            },
            &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
            &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;page&quot;: 1,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-tickets" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-tickets"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-tickets"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-tickets" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-tickets">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-tickets" data-method="GET"
      data-path="api/v1/tickets"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-tickets', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-tickets"
                    onclick="tryItOut('GETapi-v1-tickets');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-tickets"
                    onclick="cancelTryOut('GETapi-v1-tickets');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-tickets"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/tickets</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-tickets"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-tickets"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-tickets"
               value="open"
               data-component="query">
    <br>
<p>Filter by status. Example: <code>open</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>open</code></li> <li><code>pending</code></li> <li><code>resolved</code></li> <li><code>closed</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>state</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="state"                data-endpoint="GETapi-v1-tickets"
               value="active"
               data-component="query">
    <br>
<p>Filter by group of statuses: active (open, pending) or done (resolved, closed). Example: <code>active</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>active</code></li> <li><code>done</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="priority"                data-endpoint="GETapi-v1-tickets"
               value="high"
               data-component="query">
    <br>
<p>Filter by priority. Example: <code>high</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>low</code></li> <li><code>normal</code></li> <li><code>high</code></li> <li><code>urgent</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category_id"                data-endpoint="GETapi-v1-tickets"
               value=""
               data-component="query">
    <br>
<p>Filter by category ULID.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>assignee_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="assignee_id"                data-endpoint="GETapi-v1-tickets"
               value=""
               data-component="query">
    <br>
<p>Filter by assignee ULID.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>requester_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="requester_id"                data-endpoint="GETapi-v1-tickets"
               value=""
               data-component="query">
    <br>
<p>Filter by requester ULID (staff only).</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>unassigned</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="GETapi-v1-tickets" style="display: none">
            <input type="radio" name="unassigned"
                   value="1"
                   data-endpoint="GETapi-v1-tickets"
                   data-component="query"             >
            <code>true</code>
        </label>
        <label data-endpoint="GETapi-v1-tickets" style="display: none">
            <input type="radio" name="unassigned"
                   value="0"
                   data-endpoint="GETapi-v1-tickets"
                   data-component="query"             >
            <code>false</code>
        </label>
    <br>
<p>Only tickets without an assignee. Example: <code>false</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>overdue</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="GETapi-v1-tickets" style="display: none">
            <input type="radio" name="overdue"
                   value="1"
                   data-endpoint="GETapi-v1-tickets"
                   data-component="query"             >
            <code>true</code>
        </label>
        <label data-endpoint="GETapi-v1-tickets" style="display: none">
            <input type="radio" name="overdue"
                   value="0"
                   data-endpoint="GETapi-v1-tickets"
                   data-component="query"             >
            <code>false</code>
        </label>
    <br>
<p>Only unresolved tickets with a missed SLA deadline. Example: <code>false</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-tickets"
               value="printer"
               data-component="query">
    <br>
<p>Search subject and description. Must not be greater than 100 characters. Example: <code>printer</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETapi-v1-tickets"
               value="-created_at"
               data-component="query">
    <br>
<p>Sort field; prefix with - for descending. Example: <code>-created_at</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>created_at</code></li> <li><code>-created_at</code></li> <li><code>updated_at</code></li> <li><code>-updated_at</code></li> <li><code>priority</code></li> <li><code>-priority</code></li> <li><code>status</code></li> <li><code>-status</code></li> <li><code>first_response_due_at</code></li> <li><code>-first_response_due_at</code></li> <li><code>resolution_due_at</code></li> <li><code>-resolution_due_at</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-tickets"
               value="25"
               data-component="query">
    <br>
<p>Page size (1-100). Must be at least 1. Must not be greater than 100. Example: <code>25</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-tickets"
               value="1"
               data-component="query">
    <br>
<p>Page number. Must be at least 1. Example: <code>1</code></p>
            </div>
                </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>open</code></li> <li><code>pending</code></li> <li><code>resolved</code></li> <li><code>closed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>low</code></li> <li><code>normal</code></li> <li><code>high</code></li> <li><code>urgent</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>assignee</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>requester</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>last_message</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Latest public message, as an excerpt (ticket listing only).</p>
            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>author_role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>resolved_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>closed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>sla</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="tickets-POSTapi-v1-tickets">Open a ticket</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-tickets">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8090/api/v1/tickets" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"subject\": \"Printer on 3rd floor is jammed\",
    \"description\": \"It shows error E-42 since this morning.\",
    \"priority\": \"normal\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "subject": "Printer on 3rd floor is jammed",
    "description": "It shows error E-42 since this morning.",
    "priority": "normal"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-tickets">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5mfk1z9vqx157hqtyzd&quot;,
        &quot;subject&quot;: &quot;Eius et animi quos velit et.&quot;,
        &quot;description&quot;: &quot;Nihil accusantium harum mollitia modi deserunt. Ab provident perspiciatis quo omnis nostrum aut adipisci. Nostrum qui commodi incidunt iure.&quot;,
        &quot;status&quot;: &quot;open&quot;,
        &quot;priority&quot;: &quot;urgent&quot;,
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5merv7bhfwrcn6vdwj6&quot;,
            &quot;name&quot;: &quot;Quis Adipisci&quot;,
            &quot;slug&quot;: &quot;quis-adipisci&quot;,
            &quot;description&quot;: &quot;Fugit deleniti distinctio eum doloremque id aut libero.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;requester&quot;: {
            &quot;id&quot;: &quot;01m50gf5mdc5xnss6h1j9h1smy&quot;,
            &quot;name&quot;: &quot;Mina Bauch&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;assignee&quot;: {
            &quot;id&quot;: &quot;01m50gf5merv7bhfwrcn6vdwj5&quot;,
            &quot;name&quot;: &quot;Domingo Leuschke&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;resolved_at&quot;: null,
        &quot;closed_at&quot;: null,
        &quot;sla&quot;: {
            &quot;first_response_at&quot;: null,
            &quot;first_response_due_at&quot;: &quot;2026-10-15T19:41:18+00:00&quot;,
            &quot;resolution_due_at&quot;: &quot;2026-10-15T23:26:18+00:00&quot;,
            &quot;is_overdue&quot;: false
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-tickets" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-tickets"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-tickets"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-tickets" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-tickets">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-tickets" data-method="POST"
      data-path="api/v1/tickets"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-tickets', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-tickets"
                    onclick="tryItOut('POSTapi-v1-tickets');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-tickets"
                    onclick="cancelTryOut('POSTapi-v1-tickets');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-tickets"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/tickets</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-tickets"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-tickets"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subject</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subject"                data-endpoint="POSTapi-v1-tickets"
               value="Printer on 3rd floor is jammed"
               data-component="body">
    <br>
<p>Short summary. Must be at least 3 characters. Must not be greater than 200 characters. Example: <code>Printer on 3rd floor is jammed</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-tickets"
               value="It shows error E-42 since this morning."
               data-component="body">
    <br>
<p>Full description of the problem. Must not be greater than 20000 characters. Example: <code>It shows error E-42 since this morning.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="priority"                data-endpoint="POSTapi-v1-tickets"
               value="normal"
               data-component="body">
    <br>
<p>low, normal, high or urgent. Example: <code>normal</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>low</code></li> <li><code>normal</code></li> <li><code>high</code></li> <li><code>urgent</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category_id"                data-endpoint="POSTapi-v1-tickets"
               value=""
               data-component="body">
    <br>
<p>Category ULID of the same tenant. Must match an existing stored value.</p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>open</code></li> <li><code>pending</code></li> <li><code>resolved</code></li> <li><code>closed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>low</code></li> <li><code>normal</code></li> <li><code>high</code></li> <li><code>urgent</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>assignee</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>requester</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>last_message</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Latest public message, as an excerpt (ticket listing only).</p>
            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>author_role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>resolved_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>closed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>sla</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="tickets-GETapi-v1-tickets--id-">Show a ticket</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-tickets--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8090/api/v1/tickets/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-tickets--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5msph52jcxnr83qmftf&quot;,
        &quot;subject&quot;: &quot;Et animi quos velit et fugiat.&quot;,
        &quot;description&quot;: &quot;Accusantium harum mollitia modi deserunt aut ab. Perspiciatis quo omnis nostrum aut adipisci quidem nostrum qui. Incidunt iure odit et et modi ipsum.&quot;,
        &quot;status&quot;: &quot;open&quot;,
        &quot;priority&quot;: &quot;normal&quot;,
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5msph52jcxnr83qmfte&quot;,
            &quot;name&quot;: &quot;Molestias Fugit&quot;,
            &quot;slug&quot;: &quot;molestias-fugit&quot;,
            &quot;description&quot;: &quot;Distinctio eum doloremque id aut libero aliquam veniam corporis.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;requester&quot;: {
            &quot;id&quot;: &quot;01m50gf5mrq4fwz242s3bv3e1j&quot;,
            &quot;name&quot;: &quot;Mr. Carey Smitham&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;assignee&quot;: {
            &quot;id&quot;: &quot;01m50gf5msph52jcxnr83qmftd&quot;,
            &quot;name&quot;: &quot;Matilda Feeney&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;resolved_at&quot;: null,
        &quot;closed_at&quot;: null,
        &quot;sla&quot;: {
            &quot;first_response_at&quot;: null,
            &quot;first_response_due_at&quot;: &quot;2026-10-15T23:26:18+00:00&quot;,
            &quot;resolution_due_at&quot;: &quot;2026-10-16T19:26:18+00:00&quot;,
            &quot;is_overdue&quot;: false
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-tickets--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-tickets--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-tickets--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-tickets--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-tickets--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-tickets--id-" data-method="GET"
      data-path="api/v1/tickets/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-tickets--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-tickets--id-"
                    onclick="tryItOut('GETapi-v1-tickets--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-tickets--id-"
                    onclick="cancelTryOut('GETapi-v1-tickets--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-tickets--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/tickets/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-tickets--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-tickets--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-v1-tickets--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                    </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>open</code></li> <li><code>pending</code></li> <li><code>resolved</code></li> <li><code>closed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>low</code></li> <li><code>normal</code></li> <li><code>high</code></li> <li><code>urgent</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>assignee</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>requester</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>last_message</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Latest public message, as an excerpt (ticket listing only).</p>
            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>author_role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>resolved_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>closed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>sla</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="tickets-PATCHapi-v1-tickets--ticket_id--status">Change ticket status</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-tickets--ticket_id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8090/api/v1/tickets/architecto/status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"resolved\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto/status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "resolved"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-tickets--ticket_id--status">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5n42ergt0810w6st5fs&quot;,
        &quot;subject&quot;: &quot;Et animi quos velit et fugiat.&quot;,
        &quot;description&quot;: &quot;Accusantium harum mollitia modi deserunt aut ab. Perspiciatis quo omnis nostrum aut adipisci quidem nostrum qui. Incidunt iure odit et et modi ipsum.&quot;,
        &quot;status&quot;: &quot;open&quot;,
        &quot;priority&quot;: &quot;normal&quot;,
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5n3472a8fgd819s3jb3&quot;,
            &quot;name&quot;: &quot;Eum Doloremque&quot;,
            &quot;slug&quot;: &quot;eum-doloremque&quot;,
            &quot;description&quot;: &quot;Aut libero aliquam veniam corporis.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;requester&quot;: {
            &quot;id&quot;: &quot;01m50gf5n0a5qrbz57p8qkb30t&quot;,
            &quot;name&quot;: &quot;Mr. Carey Smitham&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;assignee&quot;: {
            &quot;id&quot;: &quot;01m50gf5n1ejpxwpa8nfdq4wec&quot;,
            &quot;name&quot;: &quot;Janick Schultz DDS&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;resolved_at&quot;: null,
        &quot;closed_at&quot;: null,
        &quot;sla&quot;: {
            &quot;first_response_at&quot;: null,
            &quot;first_response_due_at&quot;: &quot;2026-10-15T23:26:18+00:00&quot;,
            &quot;resolution_due_at&quot;: &quot;2026-10-16T19:26:18+00:00&quot;,
            &quot;is_overdue&quot;: false
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PATCHapi-v1-tickets--ticket_id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-tickets--ticket_id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-tickets--ticket_id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-tickets--ticket_id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-tickets--ticket_id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-tickets--ticket_id--status" data-method="PATCH"
      data-path="api/v1/tickets/{ticket_id}/status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-tickets--ticket_id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-tickets--ticket_id--status"
                    onclick="tryItOut('PATCHapi-v1-tickets--ticket_id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-tickets--ticket_id--status"
                    onclick="cancelTryOut('PATCHapi-v1-tickets--ticket_id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-tickets--ticket_id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/tickets/{ticket_id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-tickets--ticket_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-tickets--ticket_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_id"                data-endpoint="PATCHapi-v1-tickets--ticket_id--status"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-tickets--ticket_id--status"
               value="resolved"
               data-component="body">
    <br>
<p>open, pending, resolved or closed. Customers may only use open or closed. Example: <code>resolved</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>open</code></li> <li><code>pending</code></li> <li><code>resolved</code></li> <li><code>closed</code></li></ul>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>open</code></li> <li><code>pending</code></li> <li><code>resolved</code></li> <li><code>closed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>low</code></li> <li><code>normal</code></li> <li><code>high</code></li> <li><code>urgent</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>assignee</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>requester</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>last_message</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Latest public message, as an excerpt (ticket listing only).</p>
            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>author_role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>resolved_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>closed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>sla</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                        <h2 id="tickets-PATCHapi-v1-tickets--ticket_id--assignee">Assign a ticket</h2>

<p>
</p>

<p>Staff only. Pass <code>null</code> to unassign.</p>

<span id="example-requests-PATCHapi-v1-tickets--ticket_id--assignee">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8090/api/v1/tickets/architecto/assignee" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8090/api/v1/tickets/architecto/assignee"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-tickets--ticket_id--assignee">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01m50gf5nhbx0182gbzayy473q&quot;,
        &quot;subject&quot;: &quot;Et animi quos velit et fugiat.&quot;,
        &quot;description&quot;: &quot;Accusantium harum mollitia modi deserunt aut ab. Perspiciatis quo omnis nostrum aut adipisci quidem nostrum qui. Incidunt iure odit et et modi ipsum.&quot;,
        &quot;status&quot;: &quot;open&quot;,
        &quot;priority&quot;: &quot;normal&quot;,
        &quot;category&quot;: {
            &quot;id&quot;: &quot;01m50gf5ngsncsqkkyydn175jb&quot;,
            &quot;name&quot;: &quot;Aliquam Veniam&quot;,
            &quot;slug&quot;: &quot;aliquam-veniam&quot;,
            &quot;description&quot;: &quot;Dolorem mollitia deleniti nemo.&quot;,
            &quot;sla_targets&quot;: {
                &quot;low&quot;: {
                    &quot;first_response_minutes&quot;: 480,
                    &quot;resolution_minutes&quot;: 4320,
                    &quot;is_custom&quot;: false
                },
                &quot;normal&quot;: {
                    &quot;first_response_minutes&quot;: 240,
                    &quot;resolution_minutes&quot;: 1440,
                    &quot;is_custom&quot;: false
                },
                &quot;high&quot;: {
                    &quot;first_response_minutes&quot;: 60,
                    &quot;resolution_minutes&quot;: 480,
                    &quot;is_custom&quot;: false
                },
                &quot;urgent&quot;: {
                    &quot;first_response_minutes&quot;: 15,
                    &quot;resolution_minutes&quot;: 240,
                    &quot;is_custom&quot;: false
                }
            }
        },
        &quot;requester&quot;: {
            &quot;id&quot;: &quot;01m50gf5nfcwv1k46pgadwqa2c&quot;,
            &quot;name&quot;: &quot;Mr. Carey Smitham&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;assignee&quot;: {
            &quot;id&quot;: &quot;01m50gf5ngsncsqkkyydn175ja&quot;,
            &quot;name&quot;: &quot;Prof. Annabelle Kshlerin&quot;,
            &quot;role&quot;: &quot;customer&quot;,
            &quot;email&quot;: null
        },
        &quot;resolved_at&quot;: null,
        &quot;closed_at&quot;: null,
        &quot;sla&quot;: {
            &quot;first_response_at&quot;: null,
            &quot;first_response_due_at&quot;: &quot;2026-10-15T23:26:18+00:00&quot;,
            &quot;resolution_due_at&quot;: &quot;2026-10-16T19:26:18+00:00&quot;,
            &quot;is_overdue&quot;: false
        },
        &quot;created_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-10-15T19:26:18+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PATCHapi-v1-tickets--ticket_id--assignee" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-tickets--ticket_id--assignee"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-tickets--ticket_id--assignee"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-tickets--ticket_id--assignee" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-tickets--ticket_id--assignee">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-tickets--ticket_id--assignee" data-method="PATCH"
      data-path="api/v1/tickets/{ticket_id}/assignee"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-tickets--ticket_id--assignee', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-tickets--ticket_id--assignee"
                    onclick="tryItOut('PATCHapi-v1-tickets--ticket_id--assignee');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-tickets--ticket_id--assignee"
                    onclick="cancelTryOut('PATCHapi-v1-tickets--ticket_id--assignee');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-tickets--ticket_id--assignee"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/tickets/{ticket_id}/assignee</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-tickets--ticket_id--assignee"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-tickets--ticket_id--assignee"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_id"                data-endpoint="PATCHapi-v1-tickets--ticket_id--assignee"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the ticket. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>assignee_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="assignee_id"                data-endpoint="PATCHapi-v1-tickets--ticket_id--assignee"
               value=""
               data-component="body">
    <br>
<p>ULID of an active agent/admin, or null to unassign.</p>
        </div>
        </form>

    <h3>Response</h3>
    <h4 class="fancy-heading-panel"><b>Response Fields</b></h4>
    <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>data</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>open</code></li> <li><code>pending</code></li> <li><code>resolved</code></li> <li><code>closed</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>low</code></li> <li><code>normal</code></li> <li><code>high</code></li> <li><code>urgent</code></li></ul>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>tickets_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>assignee</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>requester</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Only shown to staff.</p>
                    </div>
                                    </details>
        </div>
                                                                    <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>last_message</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Latest public message, as an excerpt (ticket listing only).</p>
            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>author_role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

Must be one of:
<ul style="list-style-type: square;"><li><code>customer</code></li> <li><code>agent</code></li> <li><code>admin</code></li></ul>
                    </div>
                                    </details>
        </div>
                                                                    <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>resolved_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>closed_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>sla</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>first_response_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>resolution_due_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
                

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
