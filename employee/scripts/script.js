const toggle = document.getElementById("navbar-toggle");
const links = document.querySelector(".navbar-links");
toggle.addEventListener("click", () => {
  links.classList.toggle("active");
});
