(function($) {
  $(document).ready(function() {
    $("#btnAutophotoScanAjax").click(function() {
      var btn = this;
      btn.disabled = true;
      $.post(AutophotoAjax.ajaxurl, {
        action: "autophoto-scan-ajax", 
        autophotoNonce: AutophotoAjax.autophotoNonce
      }, function(response) {
        var msg = [];
        var result = response.result;
        if(result.new_albums > 0){
          var s = result.new_albums > 1 ? "s" : "";
          msg.push(result.new_albums + " new album" + s + " created.");
        }
        if(result.new_pictures > 0){
          var s = result.new_pictures > 1 ? "s" : "";
          msg.push(result.new_pictures + " new picture" + s + " created.");
        }
        if(result.error){
          msg.push("There was an error processing the scan: " + result.error);
        } else {
          msg.push("Scan completed successfully.");
        }
        alert(msg.join("\n"));
      }).fail(function(xhr, textStatus) {
        alert("An error occured: " + textStatus);
      }).always(function() {
        btn.disabled = false;
      });
      return false;
    });
  });

})(jQuery);
