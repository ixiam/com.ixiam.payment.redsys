<fieldset>
  <legend>Redsys Main Settings</legend>
  <div class="view-content">
    <div id="help">
        Redsys doesn't support ipn callbacks using SSL shared certificate in multiples websites with a single IP.<br>
        In that case you must force to use http protocl in ipn callback url
    </div>
  </div>
  <table class="form-layout">
    <tr class="crm-redsys-form-block-ipn_http">
      <td width="20%">
        {$form.ipn_http.label}
      </td>
      <td>
        {$form.ipn_http.html}
      </td>
    </tr>
  </table>
</fieldset>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
