document.addEventListener("DOMContentLoaded", function () {
  const logoutBtn = document.querySelector(".sidebar-logout");
  if (!logoutBtn) return;

  logoutBtn.addEventListener("click", function () {
    if (!confirm("Are you want to log out ?")) return;

    // Build an absolute base path for the application so this works
    // from pages in deeper folders (e.g. admin/html/*).
    // Assumption: the app is served under a top-level folder like
    // '/savore-restaurant' (common with XAMPP). We take the first
    // non-empty path segment as the project root.
    // Use root-absolute paths so logout always targets /customer/... and
    // doesn't inherit the current folder (e.g. /employee/.../customer/...)
    const logoutUrl = window.location.origin + "/customer/auth/logout.php";
    const redirectUrl = window.location.origin + "/savore-restaurant/customer/index.php";

    fetch(logoutUrl, { method: "POST" })
      .then((res) => res.json())
      .then((data) => {
        window.location.href = redirectUrl;
      })
      .catch((err) => {
        console.error("Logout error:", err);
        // Fallback redirect even on error
        window.location.href = redirectUrl;
      });
  });
});
