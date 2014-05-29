# elias-kuiter-de
## elias-kuiter-de

My website, built on top of Foundation 4 with a PHP rendering script.

- assets:
  Contains everything needed by the website such as CSS, JavaScript and SWF files,
  but also resources like images and videos.

- pages:
  Contains the pages of the website. The folder structure is projected onto the navigation bar.
  DO NOT delete the .htaccess file - it ensures that your page files are protected from public access!
  Adding a new page to the website works as follows:
  - Create a new file named "<page>.<language>.html", where page is the URL that the page will be
    shown on and language is the language code. Example file name: 'projects.de.html'
  - If there are more languages, create more files like 'projects.en.html'.
  - Edit the file (e.g. 'projects.de.html'). Fill the first line with the page name, like 'Projekte'.
    The second line contains a number that determines the page order in the navigation bar:
    0 would be the first page, and if you want to ensure that a page is the last one (for pages like
    'Impressum') set the order to maybe 999. (It is recommended to set numbers with bigger spacing,
    like 10, 20, 30 ... That helps to make changes afterwards.)
  - Either leave the third line free or fill it with a URL to some picture (this is the page's thumbnail).
    Then, from the fourth line, start to code the HTML for the page.
  - You're finished! On the next website reload, the page will show up in the navigation bar.
    Consider to add other languages too!
  If you want to use sub-navigations:
  - Create a new sub folder like 'projects'. This will cause all its comprised pages to appear in the
    subnavigation. Also the URLs will be changed: your-website.com/projects/some-page
  - In your folder, you need to create a file called 'info.<language>.html'. (For all other languages
    too of course.) This page is shown if the user clicks right on the subnavigation. Also this file
    determines the name of the subnavigation and the order (like described above).
  - If you took care of the info.html's, you can start adding pages like above!
  (The rendering script allows up to three levels of page nesting - otherwise it would get somehow messy.)

- index.php:
  Contains the rendering script. Renders a page in five basic steps:
  - determining the page the user requested (dispatch)
  - build the navigation bar
  - echo the header section
  - echo the page itself
  - echo the footer section
  For details, check out the file itself.

  To add additional CSS or JavaScript files, open index.php, navigate to the header() function and change
  the code as you like - same goes for the footer.

elias-kuiter-de is a project by Elias Kuiter - app & web design.
© Elias Kuiter 2013 - http://www.elias-kuiter.de/