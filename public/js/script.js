$(function(){

  $("#logout-form a").on("click", function(e){
    e.preventDefault();
    $("#logout-form").submit();
  });

});

