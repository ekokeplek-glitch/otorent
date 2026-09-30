/**
 * Ryokourent Catalog Filter & Interaction Script
 *
 * Lightweight vanilla JavaScript (zero jQuery dependency) for client-side
 * category filtering, empty-state toggling, and auto-selecting motor units
 * on the booking form.
 *
 * @package Ryokourent_Core
 * @since   1.0.0
 */

(function () {
  'use strict';

  function initRyokouCatalog() {
    const catalogSection = document.getElementById('katalog-motor');
    if (!catalogSection) {
      return;
    }

    const filterButtons = catalogSection.querySelectorAll('.ryokou-filter-btn');
    const motorCards = catalogSection.querySelectorAll('.ryokou-motor-card');
    const emptyState = document.getElementById('ryokou-catalog-empty');
    const resetBtn = document.getElementById('ryokou-reset-filter-btn');

    // 1. Category Tab Filtering
    filterButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        const targetCategory = this.getAttribute('data-filter') || 'all';

        // Update active button state
        filterButtons.forEach(function (b) {
          b.classList.remove('active');
          b.setAttribute('aria-selected', 'false');
        });
        this.classList.add('active');
        this.setAttribute('aria-selected', 'true');

        let visibleCount = 0;

        motorCards.forEach(function (card) {
          const cardCategories = (card.getAttribute('data-category') || '').trim().split(/\s+/);
          const isMatch = targetCategory === 'all' || cardCategories.indexOf(targetCategory) !== -1;

          if (isMatch) {
            card.style.display = '';
            card.style.opacity = '0';
            card.style.transform = 'translateY(8px)';
            requestAnimationFrame(function () {
              card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
              card.style.opacity = '1';
              card.style.transform = 'translateY(0)';
            });
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });

        // Toggle Empty State
        if (emptyState) {
          emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
      });
    });

    // Reset filter button inside empty state notice
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        const allBtn = catalogSection.querySelector('.ryokou-filter-btn[data-filter="all"]');
        if (allBtn) {
          allBtn.click();
        }
      });
    }

    // 2. "Sewa Sekarang" Motor Preselection Handler
    const selectButtons = catalogSection.querySelectorAll('.ryokou-btn-select-motor');
    selectButtons.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        const motorId = this.getAttribute('data-motor-id');
        const motorName = this.getAttribute('data-motor-name');

        // Locate potential booking form select inputs
        const targetSelect = document.querySelector('#rented_motor_id, select[name="rented_motor_id"], #ryokourent_booking_motor');
        const bookingForm = document.querySelector('#booking-form, #ryokourent-booking-form, .ryokou-booking-section');

        if (targetSelect && bookingForm) {
          e.preventDefault();

          // Try matching option by value (ID) or text (Title)
          let matched = false;
          for (let i = 0; i < targetSelect.options.length; i++) {
            const opt = targetSelect.options[i];
            if (opt.value === motorId || (motorName && opt.text.indexOf(motorName) !== -1)) {
              targetSelect.selectedIndex = i;
              matched = true;
              break;
            }
          }

          // Trigger change event to notify pricing/duration calculation engines
          const changeEvent = new Event('change', { bubbles: true });
          targetSelect.dispatchEvent(changeEvent);

          // Smooth scroll into booking form
          bookingForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  function initRyokouBookingForm() {
    const bookingForm = document.getElementById('ryokourent-booking-form') || document.querySelector('.ryokou-form-card');
    if (!bookingForm) {
      return;
    }

    const routeRadios = bookingForm.querySelectorAll('input[name="trip_destination"]');
    const motorSelect = bookingForm.querySelector('#rented_motor_id');
    const startInput = bookingForm.querySelector('#start_datetime');
    const endInput = bookingForm.querySelector('#end_datetime');
    const liveDuration = document.getElementById('ryokou-live-duration');
    const livePrice = document.getElementById('ryokou-live-price');

    // 1. Destination toggle handler (Bromo mandatory Trail CRF 150L)
    function handleRouteToggle() {
      const selectedRoute = bookingForm.querySelector('input[name="trip_destination"]:checked');
      if (!selectedRoute || !motorSelect) return;

      const isBromo = selectedRoute.value === 'bromo';

      // Update card option classes
      routeRadios.forEach(function (radio) {
        const parentLabel = radio.closest('.ryokou-route-option');
        if (parentLabel) {
          if (radio.checked) {
            parentLabel.classList.add('active');
          } else {
            parentLabel.classList.remove('active');
          }
        }
      });

      // Filter or lock motor select options
      let crfOptionIndex = -1;
      for (let i = 0; i < motorSelect.options.length; i++) {
        const opt = motorSelect.options[i];
        const isBromoReady = opt.getAttribute('data-is-bromo') === 'yes';

        if (isBromo) {
          if (isBromoReady) {
            opt.disabled = false;
            crfOptionIndex = i;
          } else if (opt.value !== '') {
            opt.disabled = true;
          }
        } else {
          opt.disabled = false;
        }
      }

      if (isBromo && crfOptionIndex !== -1) {
        motorSelect.selectedIndex = crfOptionIndex;
      }

      updateCalculation();
    }

    routeRadios.forEach(function (radio) {
      radio.addEventListener('change', handleRouteToggle);
    });

    // 2. Live Duration and Price Calculation
    function updateCalculation() {
      if (!startInput || !endInput || !motorSelect) return;

      const startDateVal = startInput.value;
      const endDateVal = endInput.value;

      if (!startDateVal || !endDateVal) return;

      const start = new Date(startDateVal);
      const end = new Date(endDateVal);

      if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
        if (liveDuration) liveDuration.textContent = 'Jadwal belum valid';
        if (livePrice) livePrice.textContent = 'Rp 0';
        return;
      }

      const diffMs = end.getTime() - start.getTime();
      const diffHours = Math.round((diffMs / (1000 * 60 * 60)) * 10) / 10;

      // Tolerance of 2 hours overtime
      let days = 1;
      if (diffHours <= 26) {
        days = 1;
      } else {
        const extraHours = diffHours - 24;
        days = 1 + Math.ceil(Math.max(0, extraHours - 2) / 24);
      }

      if (liveDuration) {
        liveDuration.textContent = days + ' Hari (~' + Math.round(diffHours) + ' Jam)';
      }

      // Calculate price based on selected motor daily rate
      const selectedOption = motorSelect.options[motorSelect.selectedIndex];
      const dailyPrice = selectedOption ? parseFloat(selectedOption.getAttribute('data-price-daily') || '0') : 0;

      if (livePrice) {
        if (dailyPrice > 0) {
          const totalPrice = days * dailyPrice;
          livePrice.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
        } else {
          livePrice.textContent = 'Tanya Admin';
        }
      }
    }

    if (startInput) startInput.addEventListener('change', updateCalculation);
    if (endInput) endInput.addEventListener('change', updateCalculation);
    if (motorSelect) motorSelect.addEventListener('change', updateCalculation);

    // Initial calculation on load
    updateCalculation();
  }

  // Initialize once DOM is ready
  function initAll() {
    initRyokouCatalog();
    initRyokouBookingForm();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();

