/* Melak landing page interactions */
(function () {
  "use strict";

  // ── Navbar: shadow on scroll ──────────────────────────────
  const navbar = document.getElementById("navbar");
  const onScroll = () => navbar.classList.toggle("scrolled", window.scrollY > 12);
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  // ── Mobile menu ───────────────────────────────────────────
  const toggle = document.getElementById("navToggle");
  const links = document.getElementById("navLinks");

  toggle.addEventListener("click", () => {
    const open = links.classList.toggle("open");
    toggle.classList.toggle("open", open);
    toggle.setAttribute("aria-expanded", String(open));
  });

  links.querySelectorAll("a").forEach((a) =>
    a.addEventListener("click", () => {
      links.classList.remove("open");
      toggle.classList.remove("open");
      toggle.setAttribute("aria-expanded", "false");
    })
  );

  // ── Scroll reveal ─────────────────────────────────────────
  const reveals = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            e.target.classList.add("visible");
            io.unobserve(e.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );
    reveals.forEach((el) => io.observe(el));
  } else {
    reveals.forEach((el) => el.classList.add("visible"));
  }

  // ── FAQ: lihat semua pertanyaan ───────────────────────────
  const faqMore = document.getElementById("faqMore");
  if (faqMore) {
    faqMore.addEventListener("click", () => {
      const expanded = document.body.classList.toggle("show-all-faq");
      faqMore.textContent = expanded
        ? "Sembunyikan pertanyaan tambahan"
        : "Lihat semua pertanyaan";
    });
  }

  // Accordion: hanya satu FAQ terbuka pada satu waktu
  const faqItems = document.querySelectorAll(".faq-item");
  faqItems.forEach((item) => {
    item.addEventListener("toggle", () => {
      if (item.open) {
        faqItems.forEach((other) => {
          if (other !== item) other.open = false;
        });
      }
    });
  });

  // ── Carousel testimoni (prev/next scroll) ─────────────────
  document.querySelectorAll("[data-car-prev]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const track = document.querySelector(
        '[data-car-track="' + btn.getAttribute("data-car-prev") + '"]'
      );
      if (track) track.scrollBy({ left: -track.clientWidth * 0.8, behavior: "smooth" });
    });
  });
  document.querySelectorAll("[data-car-next]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const track = document.querySelector(
        '[data-car-track="' + btn.getAttribute("data-car-next") + '"]'
      );
      if (track) track.scrollBy({ left: track.clientWidth * 0.8, behavior: "smooth" });
    });
  });

  // ── Kembali ke atas ───────────────────────────────────────
  const backToTop = document.getElementById("backToTop");
  if (backToTop) {
    const onTopScroll = () =>
      backToTop.classList.toggle("show", window.scrollY > 480);
    window.addEventListener("scroll", onTopScroll, { passive: true });
    onTopScroll();
    backToTop.addEventListener("click", () =>
      window.scrollTo({ top: 0, behavior: "smooth" })
    );
  }

  // ── Tahun footer ──────────────────────────────────────────
  const year = document.getElementById("year");
  if (year) year.textContent = String(new Date().getFullYear());
})();
