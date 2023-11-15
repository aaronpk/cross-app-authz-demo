<div class="px-4 py-5 my-5">

  <div class="col-sm-4 mx-auto">

    <h2>Hello, <?= $user->name ?>!</h2>
    <p>You successfully signed in via <code><?= $org->issuer ?></code></p>

    <details>
      <summary><b>ID Token from the IdP</b></summary>
      <textarea readonly style="width: 100%; height: 150px; font-family: courier"><?= $user->id_token ?></textarea>
    </details>

    <br>

    <button id="do-acdc-request" class="btn btn-primary">Request ACDC</button>

    <div id="acdc-request" class="hidden">
      Requesting ACDC using ID Token...<br>
      Posting to <code><?= $org->token_endpoint ?></code>

      <details>
        <summary>Params</summary>
        <pre id="acdc-params" class="response"></pre>
      </details>

    </div>

    <div id="acdc-response" class="hidden">

      <details>
        <summary>Raw response from IdP:</summary>
        <pre id="acdc-response-body" class="response"></pre>
      </details>

      <div class="success hidden">
        <p><b>Successfully got an ACDC!</b></p>

        <button id="do-token-request" class="btn btn-primary">Request Access Token</button>
      </div>

      <div class="error hidden">
        <p><b>Error getting cross-domain code</b></p>
      </div>

    </div>


    <div id="token-request" class="hidden">

      Requesting Access Token using ACDC...<br>
      Posting to <code><?= $todo_token_endpoint ?></code>

      <details>
        <summary>Params</summary>
        <pre id="token-request-params" class="response"></pre>
      </details>

      <details>
        <summary>Raw response from Token Endpoint:</summary>
        <pre id="token-response-body" class="response"></pre>
      </details>

      <div class="success hidden">
        <p><b>Successfully got an access token!</b></p>

        <a href="/wiki/" class="btn btn-primary">Continue</a>
      </div>

      <div class="error hidden">
        <p><b>Error getting access token</b></p>
      </div>

    </div>

  </div>
</div>
<script>
$(function(){

  var acdc_response;

  $("#do-acdc-request").click(function(){

    $("#acdc-request").removeClass("hidden");

    $.post("/oauth/acdc", {
      step: 'acdc',
    }, function(response){

      acdc_response = response;

      $("#acdc-response").removeClass("hidden");
      $("#acdc-params").text(response.acdc_params);
      $("#acdc-response-body").text(response.text);

      if(response.response.acdc) {
        $("#acdc-response .success").removeClass("hidden");
      } else {
        $("#acdc-response .error").removeClass("hidden");
        $("#acdc-response-body").parents()[0].setAttribute("open", true);
      }

    });

  });

  $("#do-token-request").click(function(){

    $("#token-request").removeClass("hidden");

      $.post("/oauth/acdc", {
        step: 'token',
        acdc: acdc_response.response.acdc,
      }, function (response){

        $("#token-request-params").text(response.token_request_params);

        $("#token-response-body").text(response.text);
        $("#token-response-body").parents()[0].setAttribute("open", true);

        if(response.response.access_token) {
          $("#token-request .success").removeClass("hidden");
        } else {
          $("#token-request .error").removeClass("hidden");
        }

      });

  });

});
</script>
<style>
pre.response {
  white-space: pre-wrap;
  word-wrap: break-word;
}
</style>