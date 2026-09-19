const menuBtn = document.querySelector('.btn-menu');
const nav = document.querySelector('.nav');

menuBtn.addEventListener('click', () => {
  nav.classList.toggle('active');
});
