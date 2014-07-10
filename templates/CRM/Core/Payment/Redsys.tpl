<html>
  <body>
    <form action="{$redsysURL}" method="post">
      {foreach from=$redsysParams key=key item=value}
          <input type="hidden" name="{$key}" value="{$value}" />
      {/foreach}
    </form>
    {literal}
      <script type="text/javascript">
        function submitForm(){
          var form = document.forms[0];
          form.submit();
        }
        submitForm();
      </script>
    {/literal}
</html>
