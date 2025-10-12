document.addEventListener("DOMContentLoaded", function () {
  const logoutBtn = document.querySelector(".logout-btn");
  if (!logoutBtn) return;

  logoutBtn.addEventListener("click", function () {
    if (!confirm("Are you want to log out ?")) return;

    // Use root-absolute paths so logout always targets /customer/... and
    // doesn't inherit the current folder (e.g. /employee/.../customer/...)
    const logoutUrl = window.location.origin + "/customer/auth/logout.php";
    const redirectUrl =
      window.location.origin + "/savore-restaurant/customer/index.php";

    fetch(logoutUrl, { method: "POST" })
      .then((res) => res.json())
      .then((data) => {
        window.location.href = redirectUrl;
      })
      .catch((err) => {
        console.error("Logout error:", err);
        window.location.href = redirectUrl;
      });
  });
});
