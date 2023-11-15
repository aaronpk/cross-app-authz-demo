$(function(){

  $("#logout-form a").on("click", function(e){
    e.preventDefault();
    $("#logout-form").submit();
  });

  $("#delete-access-tokens a").on("click", function(e){
    e.preventDefault();
    $("#delete-access-tokens").submit();
  });

});

