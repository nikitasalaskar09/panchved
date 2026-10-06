/**
 * Panchved Doctor Portal - Shared API Configuration & Session Utilities
 */

(function () {
  const subfolders = ['dashboard', 'patients', 'appointments', 'packages', 'courses', 'profile', 'shared'];
  const isInSubfolder = subfolders.some(folder => window.location.pathname.includes('/' + folder + '/'));

  // Configure Base API path relative to current location
  window.API_BASE_URL = window.API_BASE_URL || (isInSubfolder ? '../api' : 'api');

  // Helper to build API endpoint URLs
  window.getApiPath = function (endpoint) {
    const cleanEndpoint = endpoint.replace(/^\/?(api\/)?/, '');
    return `${window.API_BASE_URL}/${cleanEndpoint}`;
  };

  // Helper to resolve asset URLs
  window.getAssetPath = function (assetPath) {
    if (!assetPath) return '';
    if (assetPath.startsWith('http://') || assetPath.startsWith('https://') || assetPath.startsWith('//') || assetPath.startsWith('data:')) {
      return assetPath;
    }
    const cleanPath = assetPath.replace(/^\/?(\.\.\/)?(assets\/)?/, '');
    return isInSubfolder ? `../assets/${cleanPath}` : `assets/${cleanPath}`;
  };

  // Global Doctor Portal Auth Helper
  window.PanchvedAuth = {
    getDoctor: function () {
      try {
        const d = localStorage.getItem('panchved_doctor');
        return d ? JSON.parse(d) : null;
      } catch (e) {
        return null;
      }
    },
    getUser: function () {
      try {
        const u = localStorage.getItem('panchved_user');
        return u ? JSON.parse(u) : null;
      } catch (e) {
        return null;
      }
    },
    isLoggedIn: function () {
      return !!(this.getDoctor() || this.getUser());
    },
    logout: async function () {
      try {
        await fetch(window.getApiPath('logout.php'), { method: 'POST' });
      } catch (_) {}
      localStorage.removeItem('panchved_doctor');
      localStorage.removeItem('panchved_user');
      sessionStorage.clear();
      window.location.href = isInSubfolder ? '../index.html' : 'index.html';
    },
    updateUI: function () {
      const doctor = this.getDoctor() || this.getUser();
      if (!doctor) return;

      const name = doctor.full_name || doctor.name || 'Doctor';
      const role = doctor.expertise || doctor.area || doctor.role || 'Doctor';

      document.querySelectorAll('.doctor-name, #headerDoctorName').forEach(el => {
        el.textContent = name;
      });
      document.querySelectorAll('.doctor-role, #headerDoctorRole').forEach(el => {
        el.textContent = role;
      });
    }
  };

  // Wire up logout handlers globally
  document.addEventListener('DOMContentLoaded', () => {
    window.PanchvedAuth.updateUI();

    const logoutBtns = document.querySelectorAll('.logout-btn, #logoutBtn');
    logoutBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.PanchvedAuth.logout();
      });
    });
  });
})();
