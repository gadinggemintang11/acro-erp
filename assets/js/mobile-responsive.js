/**
 * Mobile Responsive Enhancement for PT. ACROPOLY INTERNATIONAL ERP
 * Handles mobile-specific interactions and optimizations
 */

(function($) {
  'use strict';

  // Detect mobile device
  const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
  const isTablet = /iPad|Android/i.test(navigator.userAgent) && window.innerWidth >= 768;

  $(document).ready(function() {
    
    // DISABLE auto-collapse - biarkan sidebar seperti desktop
    // Dropdown akan bekerja normal kebawah seperti di desktop
    
    // DISABLE semua custom dropdown logic - biarkan Bootstrap handle
    // No special positioning, no backdrop, no close button
    // Dropdown akan kebawah normal seperti desktop

    // Handle window resize - DISABLE auto toggle
    let resizeTimer;
    $(window).on('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        // Do nothing - biarkan user toggle manual atau tetap seperti desktop
      }, 250);
    });

    // Improve touch interactions on mobile
    if (isMobile) {
      // Add touch-friendly class
      $('body').addClass('touch-device');

      // Prevent double-tap zoom on buttons
      $('.btn, .nav-link').on('touchend', function(e) {
        $(this).focus();
      });

      // Close dropdowns when clicking outside
      $(document).on('touchstart', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
          $('.dropdown-menu').removeClass('show');
        }
      });

      // Smooth scroll for anchor links
      $('a[href^="#"]').on('click', function(e) {
        const href = this.getAttribute('href');
        
        // Validate href - skip if empty or just "#"
        if (!href || href === '#' || href.length <= 1) {
          return;
        }
        
        const target = $(href);
        if (target.length) {
          e.preventDefault();
          $('html, body').stop().animate({
            scrollTop: target.offset().top - 100
          }, 500);
        }
      });
    }

    // Handle table responsiveness
    $('.table').each(function() {
      if (!$(this).parent().hasClass('table-responsive')) {
        $(this).wrap('<div class="table-responsive"></div>');
      }
    });

    // Add mobile-specific classes to certain columns
    if (window.innerWidth <= 576) {
      // Hide less important columns on very small screens
      $('table th:nth-child(n+5), table td:nth-child(n+5)').addClass('d-none-mobile');
    }

    // Optimize select2 for mobile
    if (typeof $.fn.select2 !== 'undefined') {
      $('.select2').select2({
        width: '100%',
        dropdownAutoWidth: true,
        theme: 'bootstrap4',
        // Use native select on mobile for better UX
        ...(isMobile && { 
          minimumResultsForSearch: Infinity,
          templateSelection: function(data) {
            return data.text;
          }
        })
      });
    }

    // Improve form input experience on mobile
    if (isMobile) {
      // Prevent zoom on input focus for iOS
      $('input[type="text"], input[type="number"], input[type="email"], input[type="tel"], textarea, select').css({
        'font-size': '16px'
      });

      // Auto-scroll to input when focused
      $('input, select, textarea').on('focus', function() {
        setTimeout(() => {
          const element = $(this);
          const offset = element.offset().top - 100;
          $('html, body').animate({
            scrollTop: offset
          }, 300);
        }, 300);
      });
    }

    // Handle orientation change
    $(window).on('orientationchange', function() {
      // Refresh layout after orientation change
      setTimeout(function() {
        $(window).trigger('resize');
      }, 300);
    });

    // Swipe gestures - DISABLED
    // Biarkan sidebar tetap full seperti desktop
    if (typeof Hammer !== 'undefined') {
      // Do nothing - no swipe gestures
    }

    // Mobile-friendly modal adjustments
    $('.modal').on('show.bs.modal', function() {
      if (isMobile) {
        $(this).find('.modal-dialog').css({
          'margin': '0.5rem',
          'max-width': 'calc(100% - 1rem)'
        });
      }
    });

    // Optimize DataTables for mobile
    if (typeof $.fn.DataTable !== 'undefined') {
      $.extend(true, $.fn.dataTable.defaults, {
        responsive: true,
        autoWidth: false,
        columnDefs: [
          { responsivePriority: 1, targets: 0 },
          { responsivePriority: 2, targets: -1 }
        ],
        ...(window.innerWidth <= 992 && {
          pageLength: 10,
          lengthMenu: [[10, 25, 50], [10, 25, 50]]
        })
      });
    }

    // Add pull-to-refresh indicator (optional)
    let touchStartY = 0;
    $(document).on('touchstart', function(e) {
      touchStartY = e.touches[0].clientY;
    });

    $(document).on('touchmove', function(e) {
      const touchY = e.touches[0].clientY;
      const touchDiff = touchY - touchStartY;
      
      // If pulling down at the top of the page
      if (touchDiff > 0 && window.scrollY === 0) {
        // Add visual feedback if needed
      }
    });

    // Prevent iOS bounce scroll on fixed elements
    if (/(iPhone|iPad|iPod)/i.test(navigator.userAgent)) {
      $('.sidebar, .topbar').on('touchmove', function(e) {
        e.preventDefault();
      });
    }

    // Console log for debugging
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      console.log('Mobile Responsive JS loaded');
      console.log('Device:', isMobile ? 'Mobile' : isTablet ? 'Tablet' : 'Desktop');
      console.log('Screen size:', window.innerWidth + 'x' + window.innerHeight);
    }
  });

})(jQuery);
