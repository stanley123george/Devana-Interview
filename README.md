# Devana-Interview

Create a web application using Symfony that allows the user to enter any number of URLs (max 1000). The URL’s are loaded from a text area. After pressing the Submit button, front-end sends the list of URLs to the back-end (in one request) and the back-end should open an asynchronous connections to these pages and attempt to GET them (using for example stream_socket_client() with the ASYNC flag).

At the same time the front-end should create a list containing all sites and their current status.

The status contains HTTP response code and content length or “still working” if the application is still loading the given page. It should also contain a progress bar which shows the progress of loading of all pages.

The display should update in realtime with the current status of all pages.

During the execution of one batch, the user may enter another set of URLs and start a new batch in parallel, in which case the app frontend adds a separate list of sites and their statuses. User may start any number of batches in this way.

Redirection (301, 302) responses should be followed up to 5 times and the status of new page returned.

#Example
##Batch #1

site1.com HTTP response: 200 OK; length: 12345 bytes

site2.com HTTP response: still working... 

site3.com HTTP response: 301 Redirect; length: 214 bytes

redirected to www.site3.com HTTP response: still working…

Progress
[===---------] 1 / 3
 
##Batch #2

site4.com HTTP response: still working

site5.com HTTP response: still working

Progress
[---------------]  0 / 2
