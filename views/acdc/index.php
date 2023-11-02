<div class="px-4 py-5 my-5">

  <div class="col-sm-4 mx-auto">

    <p>
      <b>Got an ID Token from the IdP</b><br>
      <textarea readonly style="width: 100%; height: 150px; font-family: courier"><?= $user->id_token ?></textarea>
    </p>

    <p>
      <b>Exchanging ID Token for cross-domain code...</b><br>
      Posting to <code><?= $org->token_endpoint ?></code>
    </p>

    <p>Raw response from IDP:</p>
    <pre id="acdc-response" class="response"></pre>


    <div class="step-2 success hidden">
      <p>
        <b>Exchanging cross-domain code for access token...</b><br>
        Posting to <code><?= $todo_token_endpoint ?></code>
      </p>

      <p>Raw response from Resource App:</p>
      <pre id="token-response" class="response"></pre>
    </div>

    <div class="step-2 error hidden">
      <p><b>Error getting cross-domain code</b></p>
    </div>

    <div class="step-3 success hidden">
      <a href="/wiki/" class="btn btn-primary">Continue</a>
    </div>

  </div>
</div>
<script>
$(function(){

  $.post("/oauth/acdc", {
    step: 'acdc',
  }, function(response){

    $("#acdc-response").text(response.text);
    if(response.response.acdc) {
      $(".step-2.success").removeClass("hidden");

      $.post("/oauth/acdc", {
        step: 'token',
        acdc: response.response.acdc,
      }, function (response){

        $("#token-response").text(response.text);
        if(response.response.access_token) {
          $(".step-3.success").removeClass("hidden");
        }

      });

    } else {
      $(".step-2.error").removeClass("hidden");
    }

  });


});
</script>
<style>
pre.response {
  white-space: pre-wrap;
  word-wrap: break-word;
}
</style>