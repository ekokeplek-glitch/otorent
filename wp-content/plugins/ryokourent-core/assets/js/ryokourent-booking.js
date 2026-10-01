/**
 * Ryokourent Booking Form Client-Side Validation & AJAX Handler
 *
 * Implements lightweight vanilla JavaScript validation for Indonesian phone numbers,
 * emergency contact separation, customer name, address checks, and AJAX form submission.
 *
 * @package Ryokourent_Core
 * @since   1.0.0
 */

(function () {
  'use strict';

  function initBookingValidation() {
    const bookingForm = document.getElementById('ryokourent-booking-form') || document.querySelector('.ryokou-form-card');
    if (!bookingForm) {
      return;
    }

    const config = window.ryokouBookingConfig || {
      ajaxUrl: '/wp-admin/admin-ajax.php',
      nonce: '',
      strings: {
        submitting: 'Memproses pesanan...',
        submitText: 'Lanjutkan Pemesanan via WhatsApp',
        errName: 'Nama lengkap minimal 3 karakter sesuai e-KTP.',
        errPhone: 'Nomor WhatsApp harus nomor seluler Indonesia yang valid (10-15 digit, misal 081234567890).',
        errEmergency: 'Nomor kontak darurat harus nomor valid dan tidak boleh sama dengan nomor WhatsApp Anda.',
        errKtpAddress: 'Alamat KTP minimal 5 karakter.',
        errStayAddress: 'Tempat menginap di Malang/Batu minimal 3 karakter.',
        errMotor: 'Silakan pilih model armada motor terlebih dahulu.',
        errRateLimit: 'Terlalu banyak permintaan pemesanan. Mohon tunggu beberapa menit.',
        errGeneral: 'Mohon periksa kembali isian formulir Anda.',
      },
    };

    const nameInput = bookingForm.querySelector('#customer_name');
    const waInput = bookingForm.querySelector('#customer_whatsapp');
    const emgInput = bookingForm.querySelector('#customer_emergency_phone');
    const ktpInput = bookingForm.querySelector('#customer_ktp_address');
    const stayInput = bookingForm.querySelector('#customer_stay_address');
    const motorSelect = bookingForm.querySelector('#rented_motor_id');
    const startInput = bookingForm.querySelector('#start_datetime');
    const endInput = bookingForm.querySelector('#end_datetime');
    const liveDuration = document.getElementById('ryokou-live-duration');
    const livePrice = document.getElementById('ryokou-live-price');
    const submitBtn = bookingForm.querySelector('#ryokou-btn-submit') || bookingForm.querySelector('button[type="submit"]');

    // Helper: Normalize phone string to digits
    function cleanPhoneDigits(phone) {
      if (!phone) return '';
      let digits = phone.replace(/[^0-9]/g, '');
      if (digits.startsWith('0')) {
        digits = '62' + digits.substring(1);
      } else if (digits.startsWith('8')) {
        digits = '62' + digits;
      }
      return digits;
    }

    // Helper: Validate Indonesian cellular number
    function isValidIndonesianPhone(phone) {
      const cleaned = cleanPhoneDigits(phone);
      // Starts with 628, followed by 8 to 12 digits (total 11 to 15 digits)
      const idPhoneRegex = /^628[1-9][0-9]{7,11}$/;
      return idPhoneRegex.test(cleaned);
    }

    // Helper: Show error on field
    function setFieldError(field, message) {
      if (!field) return;
      const block = field.closest('.ryokou-field-block') || field.parentElement;
      if (!block) return;

      block.classList.add('has-error');
      let errorEl = block.querySelector('.ryokou-error-text');
      if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'ryokou-error-text';
        block.appendChild(errorEl);
      }
      errorEl.textContent = message;
    }

    // Helper: Clear error on field
    function clearFieldError(field) {
      if (!field) return;
      const block = field.closest('.ryokou-field-block') || field.parentElement;
      if (!block) return;

      block.classList.remove('has-error');
      const errorEl = block.querySelector('.ryokou-error-text');
      if (errorEl) {
        errorEl.remove();
      }
    }

    // Helper: Display top-level form alert banner
    function showFormAlert(message, type) {
      let alertEl = bookingForm.querySelector('.ryokou-form-alert');
      if (!alertEl) {
        alertEl = document.createElement('div');
        alertEl.className = 'ryokou-form-alert';
        bookingForm.insertBefore(alertEl, bookingForm.firstChild);
      }
      alertEl.className = 'ryokou-form-alert ryokou-form-alert-' + (type || 'error');
      alertEl.innerHTML = '<span class="ryokou-alert-icon">⚠️</span> <span class="ryokou-alert-msg">' + message + '</span>';
      alertEl.style.display = 'flex';
      alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearFormAlert() {
      const alertEl = bookingForm.querySelector('.ryokou-form-alert');
      if (alertEl) {
        alertEl.style.display = 'none';
        alertEl.textContent = '';
      }
    }

    // Live validation listeners
    if (nameInput) {
      nameInput.addEventListener('blur', function () {
        const val = this.value.trim();
        if (val.length > 0 && val.length < 3) {
          setFieldError(this, config.strings.errName);
        } else if (val.length >= 3) {
          clearFieldError(this);
        }
      });
      nameInput.addEventListener('input', function () {
        if (this.value.trim().length >= 3) {
          clearFieldError(this);
        }
      });
    }

    if (waInput) {
      waInput.addEventListener('blur', function () {
        const val = this.value.trim();
        if (val.length > 0 && !isValidIndonesianPhone(val)) {
          setFieldError(this, config.strings.errPhone);
        } else if (isValidIndonesianPhone(val)) {
          clearFieldError(this);
        }
      });
      waInput.addEventListener('input', function () {
        if (isValidIndonesianPhone(this.value.trim())) {
          clearFieldError(this);
        }
        // Also re-check emergency phone if both are filled
        if (emgInput && emgInput.value.trim().length > 0) {
          const waClean = cleanPhoneDigits(this.value.trim());
          const emgClean = cleanPhoneDigits(emgInput.value.trim());
          if (waClean && emgClean && waClean === emgClean) {
            setFieldError(emgInput, config.strings.errEmergency);
          } else if (isValidIndonesianPhone(emgInput.value.trim())) {
            clearFieldError(emgInput);
          }
        }
      });
    }

    if (emgInput) {
      emgInput.addEventListener('blur', function () {
        const val = this.value.trim();
        if (val.length > 0) {
          if (!isValidIndonesianPhone(val)) {
            setFieldError(this, config.strings.errPhone);
          } else if (waInput && cleanPhoneDigits(val) === cleanPhoneDigits(waInput.value.trim())) {
            setFieldError(this, config.strings.errEmergency);
          } else {
            clearFieldError(this);
          }
        }
      });
      emgInput.addEventListener('input', function () {
        const val = this.value.trim();
        if (isValidIndonesianPhone(val)) {
          if (waInput && cleanPhoneDigits(val) === cleanPhoneDigits(waInput.value.trim())) {
            setFieldError(this, config.strings.errEmergency);
          } else {
            clearFieldError(this);
          }
        }
      });
    }

    if (ktpInput) {
      ktpInput.addEventListener('blur', function () {
        if (this.value.trim().length > 0 && this.value.trim().length < 5) {
          setFieldError(this, config.strings.errKtpAddress);
        } else if (this.value.trim().length >= 5) {
          clearFieldError(this);
        }
      });
      ktpInput.addEventListener('input', function () {
        if (this.value.trim().length >= 5) {
          clearFieldError(this);
        }
      });
    }

    if (stayInput) {
      stayInput.addEventListener('blur', function () {
        if (this.value.trim().length > 0 && this.value.trim().length < 3) {
          setFieldError(this, config.strings.errStayAddress);
        } else if (this.value.trim().length >= 3) {
          clearFieldError(this);
        }
      });
      stayInput.addEventListener('input', function () {
        if (this.value.trim().length >= 3) {
          clearFieldError(this);
        }
      });
    }

    // Helper: Check Operating Hours (07:00 - 23:00 WIB)
    function isWithinOperatingHours(dateStr) {
      if (!dateStr) return false;
      const parts = dateStr.split('T');
      if (parts.length < 2) return false;
      const timeParts = parts[1].split(':');
      if (timeParts.length < 2) return false;
      const hour = parseInt(timeParts[0], 10);
      const min = parseInt(timeParts[1], 10);
      const totalMins = hour * 60 + min;
      // 07:00 is 420 mins; 23:00 is 1380 mins
      return totalMins >= 420 && totalMins <= 1380;
    }

    // Helper: Real-time duration and price calculation
    function updateLiveDurationAndPrice() {
      if (!startInput || !endInput) return;
      const startVal = startInput.value;
      const endVal = endInput.value;

      if (!startVal || !endVal) return;

      const startDate = new Date(startVal);
      const endDate = new Date(endVal);

      if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
        if (liveDuration) liveDuration.textContent = 'Jadwal belum valid';
        return;
      }

      let scheduleValid = true;

      // Operating hours check for start time
      if (!isWithinOperatingHours(startVal)) {
        setFieldError(startInput, 'Jam mulai sewa harus berada dalam jam operasional layanan (07:00 – 23:00 WIB).');
        scheduleValid = false;
      } else {
        clearFieldError(startInput);
      }

      // Check end datetime after start datetime
      if (endDate <= startDate) {
        setFieldError(endInput, 'Waktu selesai sewa harus lebih akhir dari waktu mulai sewa.');
        scheduleValid = false;
      } else if (!isWithinOperatingHours(endVal)) {
        setFieldError(endInput, 'Jam selesai sewa harus berada dalam jam operasional layanan (07:00 – 23:00 WIB).');
        scheduleValid = false;
      } else {
        clearFieldError(endInput);
      }

      if (!scheduleValid) {
        if (liveDuration) liveDuration.textContent = 'Jadwal belum valid';
        return;
      }

      const diffMs = endDate.getTime() - startDate.getTime();
      const diffHours = Math.round((diffMs / (1000 * 60 * 60)) * 10) / 10;

      // Minimum duration check
      if (diffHours < 1) {
        setFieldError(endInput, 'Durasi sewa minimal adalah 1 jam.');
        if (liveDuration) liveDuration.textContent = 'Minimal 1 Jam';
        return;
      }

      // 2-hour tolerance overtime calculation:
      // - Up to 26 hours = 1 day
      // - Over 26 hours: subtract 24h, deduct 2h tolerance, ceil to 24h intervals
      let billableDays = 1;
      if (diffHours <= 26) {
        billableDays = 1;
      } else {
        const extraHours = diffHours - 24;
        billableDays = 1 + Math.ceil(Math.max(0, extraHours - 2) / 24);
      }
      billableDays = Math.max(1, billableDays);

      const formattedHours = (diffHours % 1 === 0) ? diffHours.toFixed(0) : diffHours.toFixed(1);
      const durationLabel = billableDays + ' Hari (~' + formattedHours + ' Jam)';

      if (liveDuration) {
        liveDuration.textContent = durationLabel;
      }

      // Live price calculation based on selected motor rate
      if (motorSelect && livePrice) {
        const selectedOpt = motorSelect.options[motorSelect.selectedIndex];
        const dailyPrice = selectedOpt ? parseFloat(selectedOpt.getAttribute('data-price-daily') || '0') : 0;
        if (dailyPrice > 0) {
          const totalPrice = billableDays * dailyPrice;
          livePrice.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
        } else {
          livePrice.textContent = 'Tanya Admin';
        }
      }
    }

    if (startInput) {
      startInput.addEventListener('change', updateLiveDurationAndPrice);
      startInput.addEventListener('input', updateLiveDurationAndPrice);
    }
    if (endInput) {
      endInput.addEventListener('change', updateLiveDurationAndPrice);
      endInput.addEventListener('input', updateLiveDurationAndPrice);
    }
    if (motorSelect) {
      motorSelect.addEventListener('change', updateLiveDurationAndPrice);
    }
    // Run initial calculation
    updateLiveDurationAndPrice();

    // Form submit validation & AJAX transmission
    bookingForm.addEventListener('submit', function (e) {
      clearFormAlert();
      let hasError = false;
      let firstErrorField = null;

      // 1. Motor selection check
      if (motorSelect && (!motorSelect.value || motorSelect.value === '0')) {
        setFieldError(motorSelect, config.strings.errMotor);
        hasError = true;
        if (!firstErrorField) firstErrorField = motorSelect;
      } else if (motorSelect) {
        clearFieldError(motorSelect);
      }

      // 2. Schedule Validation check (Operating Hours & Duration)
      if (!startInput || !startInput.value) {
        setFieldError(startInput, 'Waktu mulai sewa wajib ditentukan.');
        hasError = true;
        if (!firstErrorField) firstErrorField = startInput;
      } else if (!isWithinOperatingHours(startInput.value)) {
        setFieldError(startInput, 'Jam mulai sewa harus berada dalam jam operasional layanan (07:00 – 23:00 WIB).');
        hasError = true;
        if (!firstErrorField) firstErrorField = startInput;
      } else {
        clearFieldError(startInput);
      }

      if (!endInput || !endInput.value) {
        setFieldError(endInput, 'Waktu selesai sewa wajib ditentukan.');
        hasError = true;
        if (!firstErrorField) firstErrorField = endInput;
      } else if (startInput && startInput.value && new Date(endInput.value) <= new Date(startInput.value)) {
        setFieldError(endInput, 'Waktu selesai sewa harus lebih akhir dari waktu mulai sewa.');
        hasError = true;
        if (!firstErrorField) firstErrorField = endInput;
      } else if (!isWithinOperatingHours(endInput.value)) {
        setFieldError(endInput, 'Jam selesai sewa harus berada dalam jam operasional layanan (07:00 – 23:00 WIB).');
        hasError = true;
        if (!firstErrorField) firstErrorField = endInput;
      } else {
        clearFieldError(endInput);
      }

      // 3. Name check
      if (!nameInput || nameInput.value.trim().length < 3) {
        setFieldError(nameInput, config.strings.errName);
        hasError = true;
        if (!firstErrorField) firstErrorField = nameInput;
      } else {
        clearFieldError(nameInput);
      }

      // 3. WhatsApp check
      if (!waInput || !isValidIndonesianPhone(waInput.value.trim())) {
        setFieldError(waInput, config.strings.errPhone);
        hasError = true;
        if (!firstErrorField) firstErrorField = waInput;
      } else {
        clearFieldError(waInput);
      }

      // 4. Emergency phone check
      if (!emgInput || !isValidIndonesianPhone(emgInput.value.trim())) {
        setFieldError(emgInput, config.strings.errPhone);
        hasError = true;
        if (!firstErrorField) firstErrorField = emgInput;
      } else if (waInput && cleanPhoneDigits(emgInput.value.trim()) === cleanPhoneDigits(waInput.value.trim())) {
        setFieldError(emgInput, config.strings.errEmergency);
        hasError = true;
        if (!firstErrorField) firstErrorField = emgInput;
      } else {
        clearFieldError(emgInput);
      }

      // 5. Origin KTP Address check
      if (!ktpInput || ktpInput.value.trim().length < 5) {
        setFieldError(ktpInput, config.strings.errKtpAddress);
        hasError = true;
        if (!firstErrorField) firstErrorField = ktpInput;
      } else {
        clearFieldError(ktpInput);
      }

      // 6. Stay Address check
      if (!stayInput || stayInput.value.trim().length < 3) {
        setFieldError(stayInput, config.strings.errStayAddress);
        hasError = true;
        if (!firstErrorField) firstErrorField = stayInput;
      } else {
        clearFieldError(stayInput);
      }

      if (hasError) {
        e.preventDefault();
        showFormAlert(config.strings.errGeneral, 'error');
        if (firstErrorField) {
          firstErrorField.focus();
        }
        return;
      }

      // Client-side passed. Intercept submit and transmit via AJAX for server-side validation & anti-spam
      e.preventDefault();

      const formData = new FormData(bookingForm);
      formData.append('action', 'ryokourent_submit_booking');

      // Set button loading state
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        const submitTextEl = submitBtn.querySelector('.ryokou-submit-text');
        if (submitTextEl) {
          submitTextEl.textContent = config.strings.submitting;
        }
      }

      fetch(config.ajaxUrl, {
        method: 'POST',
        body: formData,
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return {
              status: response.status,
              ok: response.ok,
              data: data,
            };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data.success) {
            // Restore button
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.classList.remove('loading');
              const submitTextEl = submitBtn.querySelector('.ryokou-submit-text');
              if (submitTextEl) {
                submitTextEl.textContent = config.strings.submitText;
              }
            }

            const errorData = result.data.data || {};
            const generalMessage = errorData.message || config.strings.errGeneral;
            showFormAlert(generalMessage, 'error');

            // Apply field errors if returned from server
            if (errorData.errors && typeof errorData.errors === 'object') {
              Object.keys(errorData.errors).forEach(function (key) {
                const targetInput = bookingForm.querySelector('[name="' + key + '"]');
                if (targetInput) {
                  setFieldError(targetInput, errorData.errors[key]);
                }
              });
            }
          } else {
            // Validation succeeded on server side!
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.classList.remove('loading');
              const submitTextEl = submitBtn.querySelector('.ryokou-submit-text');
              if (submitTextEl) {
                submitTextEl.textContent = config.strings.submitText;
              }
            }

            // In TASK-012, server validates and confirms.
            // If subsequent tasks (TASK-018) supply wa_url, redirect here.
            if (result.data.data && result.data.data.wa_url) {
              window.location.href = result.data.data.wa_url;
            } else {
              showFormAlert('✓ Data identitas berhasil divalidasi. Menghubungkan ke admin WhatsApp...', 'success');
            }
          }
        })
        .catch(function (error) {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
            const submitTextEl = submitBtn.querySelector('.ryokou-submit-text');
            if (submitTextEl) {
              submitTextEl.textContent = config.strings.submitText;
            }
          }
          showFormAlert(config.strings.errGeneral, 'error');
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBookingValidation);
  } else {
    initBookingValidation();
  }
})();
