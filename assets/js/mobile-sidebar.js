/**
 * Mobile Sidebar Enhancement - Simple & Clean
 */

(function($) {
  'use strict';

  // Detect mobile
  const isMobile = window.innerWidth <= 768;

  if (!isMobile) return; // Skip if not mobile

  $(document).ready(function() {
    
    console.log('Mobile Sidebar JS loaded');
    console.log('Screen:', window.innerWidth + 'x' + window.innerHeight);

    // Force sidebar collapsed on mobile
    $('.sidebar').addClass('toggled');
    $('#accordionSidebar').addClass('toggled');

    // Handle dropdown menu clicks
    $('.sidebar .nav-link[data-toggle="collapse"]').on('click', function(e) {
      e.preventDefault();
      
      const href = $(this).attr('href');
      
      // Skip if href is empty or just #
      if (!href || href === '#' || href === '') {
        return false;
      }
      
      const $collapse = $(href);
      
      if ($collapse.length === 0) {
        return false;
      }
      
      // Close other dropdowns
      $('.sidebar .collapse').not(href).removeClass('show');
      
      // Toggle current dropdown
      $collapse.toggleClass('show');
      
      return false;
    });

    // Close dropdown when clicking header (X button area)
    $(document).on('click', '.sidebar .collapse.show .collapse-header', function(e) {
      e.stopPropagation();
      $(this).closest('.collapse').removeClass('show');
    });

    // Close dropdown when clicking dark background
    $(document).on('click', '.sidebar .collapse.show', function(e) {
      if ($(e.target).hasClass('collapse')) {
        $(this).removeClass('show');
      }
    });

    // Prevent closing when clicking inside menu
    $(document).on('click', '.sidebar .collapse.show .collapse-inner', function(e) {
      e.stopPropagation();
    });

    // Close dropdown when clicking a menu item
    $(document).on('click', '.sidebar .collapse.show .collapse-item', function() {
      $(this).closest('.collapse').removeClass('show');
    });

    // Handle window resize
    let resizeTimer;
    $(window).on('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        if (window.innerWidth <= 768) {
          $('.sidebar').addClass('toggled');
          $('#accordionSidebar').addClass('toggled');
        } else {
          $('.sidebar').removeClass('toggled');
          $('#accordionSidebar').removeClass('toggled');
          $('.sidebar .collapse').removeClass('show');
        }
      }, 250);
    });

    // Handle orientation change
    $(window).on('orientationchange', function() {
      setTimeout(function() {
        $(window).trigger('resize');
      }, 300);
    });

    // Prevent body scroll when dropdown is open
    $(document).on('shown.bs.collapse', '.sidebar .collapse', function() {
      $('body').css('overflow', 'hidden');
    });

    $(document).on('hidden.bs.collapse', '.sidebar .collapse', function() {
      $('body').css('overflow', '');
    });

  });

})(jQuery);
