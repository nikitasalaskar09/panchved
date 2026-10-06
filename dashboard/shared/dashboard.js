/**
 * Panchved Doctor Portal - Core Frontend Logic (Shared)
 * Handles dynamic doctor profile fetching, session sync, dashboard metrics & consultations, profile updates, and UI interactions
 */

document.addEventListener('DOMContentLoaded', () => {
  // Helper for determining current path depth & API paths
  const subfolders = ['dashboard', 'patients', 'appointments', 'packages', 'courses', 'profile', 'shared'];
  const isInSubfolder = subfolders.some(folder => window.location.pathname.includes('/' + folder + '/'));

  function getApiUrl(endpoint) {
    if (window.getApiPath) return window.getApiPath(endpoint);
    const cleanEndpoint = endpoint.replace(/^\/?(api\/)?/, '');
    return (isInSubfolder ? '../api/' : 'api/') + cleanEndpoint;
  }

  // =========================================================================
  // 1. Mobile Sidebar Drawer Logic
  // =========================================================================
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  if (mobileMenuBtn && sidebar && sidebarOverlay) {
    mobileMenuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      sidebar.classList.toggle('active');
      sidebarOverlay.classList.toggle('active');
    });

    sidebarOverlay.addEventListener('click', () => {
      sidebar.classList.remove('open', 'active');
      sidebarOverlay.classList.remove('active');
    });
  }

  // Active navigation items
  const navLinks = document.querySelectorAll('.nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      if (link.getAttribute('href') && link.getAttribute('href').startsWith('#')) {
        e.preventDefault();
        navLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        if (sidebar && sidebarOverlay) {
          sidebar.classList.remove('open', 'active');
          sidebarOverlay.classList.remove('active');
        }
      }
    });
  });

  // =========================================================================
  // 2. Global Toast Notification Utility
  // =========================================================================
  let toastTimer = null;
  function showToast(message, type = 'success') {
    let toast = document.getElementById('toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast';
      toast.className = 'toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      document.body.appendChild(toast);
    }

    clearTimeout(toastTimer);
    toast.textContent = message;
    toast.className = `toast show toast-${type}`;

    toastTimer = setTimeout(() => {
      toast.classList.remove('show');
    }, 3800);
  }
  window.showToast = showToast;

  // =========================================================================
  // 3. Doctor Profile Sync Across UI Header & Pages
  // =========================================================================
  function syncDoctorUI(doctor) {
    if (!doctor) return;

    const docName = doctor.full_name || doctor.name || doctor.username || 'Doctor';
    const docRole = doctor.expertise || doctor.area || doctor.role || 'Doctor';
    const docEmail = doctor.email || doctor.doctor_email || doctor.user_email || '';
    const docPhone = doctor.phone_number || doctor.phoneNumber || doctor.phone || doctor.doctor_phone || '';

    // 1. Update Header Doctor Name everywhere
    document.querySelectorAll('.doctor-name, #headerDoctorName').forEach(el => {
      el.textContent = docName;
    });

    // 2. Update Header Doctor Role
    document.querySelectorAll('.doctor-role, #headerDoctorRole').forEach(el => {
      el.textContent = docRole;
    });

    // 3. Update Welcome subtitle on dashboard
    const welcomeDoctorName = document.getElementById('welcomeDoctorName');
    if (welcomeDoctorName) {
      welcomeDoctorName.textContent = docName;
    }
    const isDashboardPage = document.querySelector('.page-title') && document.querySelector('.page-title').textContent.trim().toLowerCase() === 'dashboard';
    const welcomeSubtitle = document.querySelector('.welcome-subtitle');
    if (welcomeSubtitle && isDashboardPage) {
      welcomeSubtitle.textContent = `Welcome Back ${docName}!`;
    }

    // 4. Update Course Checkout 'Your Details' section dynamically from database
    const nameEls = document.querySelectorAll('#displayUserName, #coursesUserName, .checkout-user-name');
    const emailEls = document.querySelectorAll('#displayUserEmail, #coursesUserEmail, .checkout-user-email');
    const phoneEls = document.querySelectorAll('#displayUserPhone, #coursesUserPhone, .checkout-user-phone');

    nameEls.forEach(el => { if (docName) el.textContent = docName; });
    emailEls.forEach(el => { if (docEmail) el.textContent = docEmail; });
    phoneEls.forEach(el => { if (docPhone) el.textContent = docPhone; });
  }
  window.syncDoctorUI = syncDoctorUI;

  // =========================================================================
  // 4. Dashboard Backend Integration (Metrics, Consultations, Pagination)
  // =========================================================================
  const consultationTableBody = document.getElementById('consultationTableBody');
  const metricToday = document.getElementById('metricTodayConsultations');
  const metricActive = document.getElementById('metricActivePatients');
  const metricPending = document.getElementById('metricPendingFollowups');
  const metricMessages = document.getElementById('metricUnreadMessages');

  const prevBtn = document.querySelector('.prev-btn') || document.getElementById('prevPageBtn');
  const nextBtn = document.querySelector('.next-btn') || document.getElementById('nextPageBtn');
  const pageIndicator = document.querySelector('.page-indicator') || document.getElementById('pageIndicator');
  const paginationInfo = document.querySelector('.pagination-info') || document.getElementById('paginationInfo');

  let currentDashboardPage = 1;
  const itemsPerPage = 3;

  function bindActionDropdowns() {
    const actionButtons = document.querySelectorAll('.action-menu-btn');
    const dropdowns = document.querySelectorAll('.action-menu-dropdown');

    actionButtons.forEach(btn => {
      btn.onclick = (e) => {
        e.stopPropagation();
        const rowId = btn.getAttribute('data-row');
        const targetDropdown = document.getElementById(`dropdown-${rowId}`);

        dropdowns.forEach(d => {
          if (d !== targetDropdown) d.classList.remove('show');
        });

        if (targetDropdown) {
          targetDropdown.classList.toggle('show');
        }
      };
    });
  }

  // Close dropdowns on outside click
  document.addEventListener('click', () => {
    document.querySelectorAll('.action-menu-dropdown').forEach(d => d.classList.remove('show'));
  });

  function renderConsultationRows(consultations) {
    if (!consultationTableBody) return;

    if (!consultations || consultations.length === 0) {
      consultationTableBody.innerHTML = `
        <tr>
          <td colspan="6" style="text-align:center; padding: 28px 16px; color: #64748b; font-weight: 500;">
            No consultation
          </td>
        </tr>
      `;
      return;
    }

    let rowsHtml = '';
    consultations.forEach((item, index) => {
      const rowId = index + 1;
      const patientDetailsUrl = item.patient_id ? `${isInSubfolder ? '../patients/' : 'patients/'}patient-details.html?id=${item.patient_id}` : `${isInSubfolder ? '../patients/' : 'patients/'}patient-details.html`;
      const prescriptionUrl = item.patient_id ? `${isInSubfolder ? '../appointments/' : 'appointments/'}add-prescription.html?patient_id=${item.patient_id}` : `${isInSubfolder ? '../appointments/' : 'appointments/'}add-prescription.html`;

      rowsHtml += `
        <tr>
          <td class="cell-patient">
            <a href="${patientDetailsUrl}" class="patient-profile-link" title="View ${item.patient_name} details">
              <div class="patient-avatar">${item.patient_initials}</div>
              <div class="patient-details">
                <span class="patient-name">${item.patient_name}</span>
                <span class="patient-id">${item.patient_code}</span>
              </div>
            </a>
          </td>
          <td class="cell-time">${item.appointment_time}</td>
          <td class="cell-package">${item.package_name}</td>
          <td class="cell-status">
            <span class="status-badge ${item.status_class}">${item.status}</span>
          </td>
          <td class="cell-meeting">
            <a href="${item.meeting_link}" target="_blank" rel="noopener noreferrer" class="meet-link" title="Open Google Meet link">
              <span>meet.google...</span>
              <svg class="meet-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
              </svg>
            </a>
          </td>
          <td class="cell-action">
            <div class="action-dropdown-wrapper">
              <button class="action-menu-btn" aria-label="Actions for ${item.patient_name}" data-row="${rowId}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="12" cy="5" r="2"></circle>
                  <circle cx="12" cy="12" r="2"></circle>
                  <circle cx="12" cy="19" r="2"></circle>
                </svg>
              </button>
              <div class="action-menu-dropdown" id="dropdown-${rowId}">
                <a href="${item.meeting_link}" target="_blank" class="dropdown-item" style="display:block; text-decoration:none; color:inherit;">Start Consultation</a>
                <a href="${patientDetailsUrl}" class="dropdown-item" style="display:block; text-decoration:none; color:inherit;">View Patient Details</a>
                <a href="${prescriptionUrl}" class="dropdown-item" style="display:block; text-decoration:none; color:inherit;">Add Prescription</a>
              </div>
            </div>
          </td>
        </tr>
      `;
    });

    consultationTableBody.innerHTML = rowsHtml;
    bindActionDropdowns();
  }

  function fetchDashboardData(page = 1) {
    if (!consultationTableBody && !metricToday) return;

    fetch(getApiUrl(`dashboard_data.php?page=${page}&limit=${itemsPerPage}`), {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    })
    .then(async (res) => {
      if (!res.ok) return null;
      return res.json();
    })
    .then(data => {
      if (data && data.success) {
        // Sync doctor header
        if (data.doctor) {
          localStorage.setItem('panchved_doctor', JSON.stringify(data.doctor));
          syncDoctorUI(data.doctor);
        }

        // Update 4 Metrics dynamically from DB
        if (data.metrics) {
          if (metricToday) metricToday.textContent = data.metrics.today_consultations ?? 0;
          if (metricActive) metricActive.textContent = data.metrics.active_patients ?? 0;
          if (metricPending) metricPending.textContent = data.metrics.pending_followups ?? 0;
          if (metricMessages) metricMessages.textContent = data.metrics.unread_messages ?? 0;
        }

        // Render Table Rows dynamically
        renderConsultationRows(data.consultations || []);

        // Update Pagination Controls dynamically
        if (data.pagination) {
          currentDashboardPage = data.pagination.current_page || 1;
          const totalPages = data.pagination.total_pages || 1;
          const totalItems = data.pagination.total_items || 0;

          if (pageIndicator) {
            pageIndicator.textContent = `${totalItems > 0 ? currentDashboardPage : 0} of ${totalPages}`;
          }
          if (paginationInfo) {
            if (totalItems > 0) {
              paginationInfo.textContent = `Showing ${data.pagination.start_item} to ${data.pagination.end_item} of ${totalItems} items`;
            } else {
              paginationInfo.textContent = 'Showing 0 of 0 items';
            }
          }

          if (prevBtn) prevBtn.disabled = currentDashboardPage <= 1;
          if (nextBtn) nextBtn.disabled = currentDashboardPage >= totalPages;
        }
      } else {
        if (consultationTableBody) {
          consultationTableBody.innerHTML = `
            <tr>
              <td colspan="6" style="text-align:center; padding: 28px 16px; color: #64748b; font-weight: 500;">
                No consultation
              </td>
            </tr>
          `;
        }
      }
    })
    .catch(err => {
      console.warn('Dashboard data fetch error:', err);
      if (consultationTableBody) {
        consultationTableBody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align:center; padding: 28px 16px; color: #64748b; font-weight: 500;">
              No consultation
            </td>
          </tr>
        `;
      }
    });
  }

  // Pagination Button Click Handlers
  if (prevBtn && nextBtn) {
    prevBtn.addEventListener('click', () => {
      if (currentDashboardPage > 1) {
        currentDashboardPage--;
        fetchDashboardData(currentDashboardPage);
      }
    });

    nextBtn.addEventListener('click', () => {
      currentDashboardPage++;
      fetchDashboardData(currentDashboardPage);
    });
  }

  // =========================================================================
  // 5. Fetch Doctor Profile from Admin Database (Cross-Page Header Sync)
  // =========================================================================
  function populateProfileForm(doctor) {
    if (!doctor) return;

    const fullNameInput = document.getElementById('profileFullName');
    const dobInput = document.getElementById('profileDOB');
    const phoneInput = document.getElementById('profilePhone');
    const genderInput = document.getElementById('profileGender');
    const emailInput = document.getElementById('profileEmail');
    const yoeInput = document.getElementById('profileYOE');
    const expertiseInput = document.getElementById('profileExpertise');
    const areaInput = document.getElementById('profileArea');
    const regNoInput = document.getElementById('profileRegNo');
    const hprInput = document.getElementById('profileHPR');

    if (fullNameInput && doctor.full_name !== undefined) fullNameInput.value = doctor.full_name;
    if (dobInput) dobInput.value = doctor.date_of_birth_display || doctor.date_of_birth_iso || doctor.date_of_birth || '1990-01-01';
    if (phoneInput && doctor.phone_number !== undefined) phoneInput.value = doctor.phone_number;
    if (genderInput && doctor.gender !== undefined) genderInput.value = doctor.gender;
    if (emailInput && doctor.email !== undefined) emailInput.value = doctor.email;
    if (yoeInput && doctor.years_of_experience !== undefined) yoeInput.value = doctor.years_of_experience;
    if (expertiseInput && doctor.expertise !== undefined) expertiseInput.value = doctor.expertise;
    if (areaInput && doctor.area !== undefined) areaInput.value = doctor.area;
    if (regNoInput && doctor.registration_number !== undefined) regNoInput.value = doctor.registration_number;
    if (hprInput && doctor.hpr_registration_number !== undefined) hprInput.value = doctor.hpr_registration_number;
  }

  function fetchDoctorProfile() {
    // 1. Immediately apply cached local storage if available
    let cachedDoctor = null;
    try {
      const cachedDoctorStr = localStorage.getItem('panchved_doctor');
      if (cachedDoctorStr) {
        cachedDoctor = JSON.parse(cachedDoctorStr);
      } else {
        const cachedUserStr = localStorage.getItem('panchved_user');
        if (cachedUserStr) {
          cachedDoctor = JSON.parse(cachedUserStr);
        }
      }
      if (cachedDoctor) {
        syncDoctorUI(cachedDoctor);
        populateProfileForm(cachedDoctor);
      }
    } catch (e) {
      console.warn('Error reading cached doctor info:', e);
    }

    // 2. Fetch fresh doctor profile from Database API
    let queryParams = '';
    if (cachedDoctor) {
      const params = new URLSearchParams();
      if (cachedDoctor.id) params.append('doctor_id', cachedDoctor.id);
      if (cachedDoctor.phone_number || cachedDoctor.phone) params.append('phone', cachedDoctor.phone_number || cachedDoctor.phone);
      if (cachedDoctor.email) params.append('email', cachedDoctor.email);
      const q = params.toString();
      if (q) queryParams = `?${q}`;
    }

    fetch(getApiUrl(`get_profile.php${queryParams}`), {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    })
    .then(async res => {
      if (!res.ok) return null;
      return res.json();
    })
    .then(data => {
      if (data && data.success && data.doctor) {
        localStorage.setItem('panchved_doctor', JSON.stringify(data.doctor));
        syncDoctorUI(data.doctor);
        populateProfileForm(data.doctor);
      }
    })
    .catch(err => {
      console.warn('Doctor profile fetch error (using local storage):', err);
    });
  }

  // =========================================================================
  // 6. Profile Form Submission Handler (Profile.html)
  // =========================================================================
  const profileForm = document.getElementById('doctorProfileForm');
  const profileUpdateBtn = document.getElementById('profileUpdateBtn');

  if (profileForm) {
    profileForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      let origBtnText = 'Update Details';
      if (profileUpdateBtn) {
        origBtnText = profileUpdateBtn.textContent;
        profileUpdateBtn.disabled = true;
        profileUpdateBtn.textContent = 'Updating...';
      }

      // Collect values
      const fullName = document.getElementById('profileFullName')?.value.trim();
      const dob = document.getElementById('profileDOB')?.value;
      const phone = document.getElementById('profilePhone')?.value.trim();
      const gender = document.getElementById('profileGender')?.value;
      const email = document.getElementById('profileEmail')?.value.trim();
      const yoe = document.getElementById('profileYOE')?.value;
      const expertise = document.getElementById('profileExpertise')?.value.trim();
      const area = document.getElementById('profileArea')?.value.trim();
      const regNo = document.getElementById('profileRegNo')?.value.trim();
      const hpr = document.getElementById('profileHPR')?.value.trim();

      let doctorId = null;
      try {
        const stored = JSON.parse(localStorage.getItem('panchved_doctor') || localStorage.getItem('panchved_user') || '{}');
        doctorId = stored.id || null;
      } catch (_) {}

      const payload = {
        doctor_id: doctorId,
        full_name: fullName,
        date_of_birth: dob,
        phone_number: phone,
        gender: gender,
        email: email,
        years_of_experience: yoe,
        expertise: expertise,
        area: area,
        registration_number: regNo,
        hpr_registration_number: hpr
      };

      try {
        const response = await fetch(getApiUrl('update_profile.php'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        const result = await response.json().catch(() => null);

        if (response.ok && result && result.success) {
          showToast('Profile updated successfully!', 'success');
          if (result.doctor) {
            localStorage.setItem('panchved_doctor', JSON.stringify(result.doctor));
            syncDoctorUI(result.doctor);
            populateProfileForm(result.doctor);
          }
        } else {
          showToast(result?.message || 'Failed to update profile.', 'error');
        }
      } catch (err) {
        console.error('Profile update error:', err);
        showToast('Network error while saving profile.', 'error');
      } finally {
        if (profileUpdateBtn) {
          profileUpdateBtn.disabled = false;
          profileUpdateBtn.textContent = origBtnText;
        }
      }
    });
  }

  // =========================================================================
  // 7. Logout Handler
  // =========================================================================
  const logoutButtons = document.querySelectorAll('.logout-btn, #logoutBtn');
  logoutButtons.forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();

      try {
        await fetch(getApiUrl('logout.php'), { method: 'POST' });
      } catch (_) {}

      localStorage.removeItem('panchved_doctor');
      localStorage.removeItem('panchved_user');
      sessionStorage.clear();

      window.location.href = isInSubfolder ? '../index.html' : 'index.html';
    });
  });

  // Startup initialization
  fetchDoctorProfile();
  fetchDashboardData(1);
});
