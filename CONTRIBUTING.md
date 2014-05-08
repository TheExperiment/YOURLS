Contributing to YOURLS
======================

Please take a moment to review this document, or you will see your issue/pull request closed with *harsh comments*. :wink:

Following these guidelines helps to communicate that you respect the time of
the developers managing and developing for free this open source project during their free time.
Thank you for this, and in return we will reciprocate that respect in addressing your issue
or assessing patches with goodwill.

Issue Tracker
-------------

The [issue tracker](https://github.com/YOURLS/YOURLS/issues) is
the preferred channel for [bug reports](#bug-reports), [feature requests](#feature-requests)
and [submitting pull requests](#pull-requests), but please respect the following
restrictions:

* Please **do not** use the issue tracker for personal support requests. Use sites such as
  [Stack Overflow](http://stackoverflow.com) instead.

* Please **do not** open issues or pull requests regarding the code in
  YOURLS' dependencies (open them in their respective repositories).

* Please **[:exclamation: search](https://github.com/YOURLS/YOURLS/search?type=Issues)** before you file a new issue or request.

Guidelines
----------
Your bible must be the **[:octocat: GitHub Contributing Guide](https://guides.github.com/activities/contributing-to-open-source/#contributing)**.

### Bug Reports

A bug is a _demonstrable problem_ that is caused by the code in the repository.
Good bug reports are extremely helpful - thank you!

#### Rules

1. **Make sure it is a bug**  
   YOURLS has been installed by a few thousands people before you, so if
   you're about to report something very trivial that would have been noticed for long, maybe it's
   not a bug, but only a problem on your side (YOURLS config, server config, or a good old
   _PEBKAC_ case...)

2. **Use the GitHub issue search**  
   Check if the issue has already been reported. Reporting duplicates is a waste of
   time for everyone.  
  [Search](https://github.com/YOURLS/YOURLS/search?type=Issues) in **all issues**, open and closed.

3. **Check if the issue has been fixed**  
   Try to reproduce it using the [latest code](https://github.com/YOURLS/YOURLS/archive/master.zip).  
   Maybe it has been fixed since the last stable release.

4. **Give details**  
   Give any information that is relevant to the bug: 
   * YOURLS & MySQL & PHP versions
   * Server Software
   * Browser name & version
   * ...
   
   What is the expected output? What do you see instead? See the report example below.  

6. **Be accurate**  
   Vague statements (_"it makes an error"_) are useless and will probably get
   your issue closed without particular kindness. A good bug report must not leave others needing
   to chase you up for more information.

7. **Isolate the problem**  
   Isolate the problem as much as you can, reduce to the bare minimum required to reproduce the issue.
   Don't describe a general situation that doesn't work as expected and just count on us to pin
   point the problem. 

8. **Use understandable English**  
   This project gathers people from all over the world, and that
   is awesome. We know that not everyone writes and read English fluently, but that is the only
   way to have all these people collaborate. If you're not comfortable with writing English, please
   get help from a friend and avoid automatic translators. Aim for clear short sentences with
   punctuation, no abbreviations.

#### Template

Use the following sample to begin on a good base, or [open a new templated issue](https://github.com/YOURLS/YOURLS/issues/new?title=Descriptive+issue+title&body=Before+any+bug+report%3a%0d%0a-+%5b+%5d+Check+you+are+using+the+LATEST+release+or+the+development+branch%0d%0a-+%5b+%5d+Make+sure+you+have+SEARCHED+closed+issues+first%0d%0a-+%5b+%5d+Read+the+GUIDELINES+linked+in+the+yellow+notice+box+above%0d%0a-+%5b+%5d+Now+please+DELETE+these+first+lines%0d%0a%0d%0a---%0d%0a%0d%0a%23%23%23+Bug+description%0d%0aComplete+description+why+this+specific+YOURLS+behavior+is+a+bug.++%0d%0aAlso+add+any+output+or+log+which+should+help+resolution.%0d%0a%0d%0a%23%23%23+Reproduction%0d%0a1.+First+step%0d%0a2.+Second+step%0d%0a3.+...%0d%0a%0d%0a%23%23%23+Technical+details%0d%0aAny+other+useful+information+depending+on+context%3a%0d%0a*+Versions%0d%0a++-+YOURLS%0d%0a++-+PHP%0d%0a++-+MySQL%0d%0a*+Environment%0d%0a++-+Server+software+%2f+OS%0d%0a++-+Server+configuration%0d%0a++-+Browser+version%0d%0a*+...)
directly.

> Short and descriptive bug report title -- helpful for others to search
>
> ***Bug description***  
> Complete description why this specific YOURLS behavior is a bug.  
> Also add any output or log which should help resolution.
> 
> ***Reproduction***  
> 1. First step  
> 2. Second step  
> 3. ...
> 
> ***Technical details***  
> Any other useful information depending on context:
> * Versions
>   - YOURLS
>   - PHP
>   - MySQL
> * Environment
>   - Server software / OS
>   - Server configuration
>   - Browser version
> * ...

### Feature Requests

Feature requests are welcome. 

Take a moment to find out whether your idea fits the scope and
goals of the project. Check also the [Road Map](https://github.com/YOURLS/YOURLS/issues/milestones),
maybe your idea is already planned.

It's up to you to make a strong case to convince the project's developers of the merits of this feature.
* Please provide as much detail and context as possible and get in touch. 
* Feel free to detail how you envision things, be they about (pseudo)code, interface, mockup, etc.

### Pull Requests

Good pull requests are a fantastic help. 

1. **Ask first**  
   Please get in touch before embarking on any significant pull request (e.g.
   implementing features, refactoring code), otherwise you risk spending a lot
   of time working on something that will not get merged into the project.

2. **Licensing**  
   By submitting a patch, you agree that your code will be licensed under the same
   hippie license that YOURLS uses, *aka* the "Do whatever the hell you want with it" license.  
   See also [MIT License](LICENSE) terms.

3. **Coding Standards**  
   Please adhere to YOURLS [Coding Standards](https://github.com/YOURLS/YOURLS/wiki/Coding-Standards).

4. **Tests**  
   Make sure you've tested your patch under different scenarios (various browsers, non default installation path, etc).  
   Write unit-tests for what you have implemented.

---
Thanks for reading! :ok_hand:
