document.addEventListener("DOMContentLoaded", function () {
  const apiKeyInput = document.getElementById("apiKey");
  const rememberCheckbox = document.getElementById("rememberKey");
  if (!apiKeyInput || !rememberCheckbox) return;

  try {
    const savedKey = localStorage.getItem("semaphore_api_key");
    if (savedKey) {
      apiKeyInput.value = savedKey;
      rememberCheckbox.checked = true;
      apiKeyInput.type = "password";
    }
  } catch (_) {}

  rememberCheckbox.addEventListener("change", function () {
    try {
      if (rememberCheckbox.checked) {
        if (apiKeyInput.value.trim().length > 0) {
          localStorage.setItem("semaphore_api_key", apiKeyInput.value.trim());
        }
      } else {
        localStorage.removeItem("semaphore_api_key");
      }
    } catch (_) {}
  });

  apiKeyInput.addEventListener("input", function () {
    try {
      if (rememberCheckbox.checked) {
        if (apiKeyInput.value.trim().length > 0) {
          localStorage.setItem("semaphore_api_key", apiKeyInput.value.trim());
        } else {
          localStorage.removeItem("semaphore_api_key");
        }
      }
    } catch (_) {}
  });
});
