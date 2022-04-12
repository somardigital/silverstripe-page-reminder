<html>
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <p>Hello $MemberName,</p>

        <p>The <b>Content Update Reminders</b> task has been run at $LastRun.Nice and is now completed.</p>

        <p>
            Ensuring content is fresh and valid on the website is key to maintaining its value to ratepayers and citizens.<br />
            It's also our best way to tell the 'GWRC story' and improve our profile and visibility in our community.
        </p>

        <p>The following pages on the GW website haven't been edited recently and might need updating.

        <h2>Summary</h2>
        <% loop $ItemsTypes %>
        <h3>$Name<h3>
        <ul>
            <% loop $GroupedItems %>
            <li>$Title: $Items.Count</li>
            <% end_loop %>
        </ul>
        <% end_loop %>

        <h2>Details</h2>
        <% loop $ItemsTypes %>
        <% loop $GroupedItems %>
            <% if $Items %>
            <h3>$Up.Name exceeding $Title reminder period date of {$Window.Nice}</h3>

            <ul>
                <% loop $Items %>
                <li>
                    <a href="$AbsoluteLink" target='_blank'>$Title</a><% if $Version %> (version #$Version)<% end_if %> <br/>
                    Last edited: $LastEdited.Ago
                </li>
                <% end_loop %>
            </ul>
            <% end_if %>
        <% end_loop %>
        <% end_loop %>

    </body>
</html>
