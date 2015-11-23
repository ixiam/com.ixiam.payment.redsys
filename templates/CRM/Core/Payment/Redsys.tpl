<html>
  <body>    
    <form action="{$redsysURL}" method="post">
  
    
    <input type="hidden" name="Ds_SignatureVersion" value="{$version}"/></br>
    <input type="hidden" name="Ds_MerchantParameters" value="{$redsysParamsJSON}"/></br>
    <input type="hidden" name="Ds_Signature" value="{$signature}"/></br>
  
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
