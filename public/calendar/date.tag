<@ attribute name="field" required="true" rtexprvalue="true">
<@ attribute name="button" required="true" rtexprvalue="true">

 <!-- calendar stylesheet -->
  <link rel="stylesheet" type="text/css" media="all" href="calendar-win2k-cold-1.css" title="win2k-cold-1" />

  <!-- main calendar program -->
  <script type="text/javascript" src="calendar.js"></script>

  <!-- language for the calendar -->
  <script type="text/javascript" src="lang/calendar-en.js"></script>

  <!-- the following script defines the Calendar.setup helper function, which makes
       adding a calendar a matter of 1 or 2 lines of code. -->
  <script type="text/javascript" src="calendar-setup.js"></script>
<script type="text/javascript">
    Calendar.setup({
        inputField     :    ${field},      // id of the input field
        ifFormat       :    "%d/%m/%Y",       // format of the input field
        button         :    ${button},   // trigger for the calendar (button ID)
        step           :    1                // show all years in drop-down boxes 
    });
</script>