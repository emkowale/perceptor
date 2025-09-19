(function($){
  function refreshRecentUploads() {
    var $container = $("#perceptor-recent");
    var $spinner   = $("#perceptor-spinner");

    $spinner.show();
    $.ajax({
      url: ajaxurl, // WordPress admin ajax URL
      type: "POST",
      data: { action: "perceptor_recent" },
      success: function(html) {
        $container.html(html);
      },
      error: function(xhr, status, err) {
        $container.html("<div class='error'>Failed to load recent uploads.</div>");
        console.error("Perceptor Ajax error:", status, err);
      },
      complete: function() {
        $spinner.hide();
      }
    });
  }

  // Run on page load
  $(document).ready(function(){
    refreshRecentUploads();

    // Refresh every 30s
    setInterval(refreshRecentUploads, 30000);
  });
})(jQuery);
