
"use strict";

/* ============================================
   VELORA MOTORS V3
   Developer: Muhammad Ahsan Awan
   Local 25-image automotive gallery
============================================ */

document.documentElement.classList.add("js");

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => [...document.querySelectorAll(selector)];

const reducedMotion = window.matchMedia(
  "(prefers-reduced-motion: reduce)"
).matches;

const angles = [
  "front",
  "side",
  "rear",
  "interior",
  "wheel"
];

const cars = [
  {
    name: "Lamborghini Revuelto",
    category: "HYBRID SUPERCAR",
    description: "An electrified V12 flagship, combining dramatic Italian design with hybrid performance.",
    engine: "V12 plug-in hybrid",
    drive: "All-wheel drive",
    character: "Flagship supercar",
    folder: "revuelto",
    files: [
      "revuelto-front.jpg",
      "revuelto-side.jpg",
      "revuelto-rear.jpg",
      "revuelto-interior.jpg",
      "revuelto-wheel.jpg"
    ]
  },
  {
    name: "Ferrari SF90 Stradale",
    category: "HYBRID SUPERCAR",
    description: "A remarkable combination of Ferrari design, a twin-turbo V8 and electrification.",
    engine: "V8 plug-in hybrid",
    drive: "All-wheel drive",
    character: "Hybrid supercar",
    folder: "sf90",
    files: [
      "sf90-front.jpg",
      "sf90-side.jpg",
      "sf90-rear.jpg",
      "sf90-interior.jpg",
      "sf90-wheel.jpg"
    ]
  },
  {
    name: "Porsche 911 GT3 RS",
    category: "TRACK PERFORMANCE",
    description: "Track-focused engineering with exceptional aerodynamics and a naturally aspirated flat-six.",
    engine: "4.0L flat-six",
    drive: "Rear-wheel drive",
    character: "Track-focused sports car",
    folder: "gt3rs",
    files: [
      "gt3rs-front (1).jpg",
      "gt3rs-side (1).jpg",
      "gt3rs-rear (1).jpg",
      "gt3rs-interior (1).jpg",
      "gt3rs-wheel (1).jpg"
    ]
  },
  {
    name: "McLaren 750S Spider",
    category: "PERFORMANCE SUPERCAR",
    description: "An open-top supercar shaped around lightweight construction and dynamic performance.",
    engine: "4.0L twin-turbo V8",
    drive: "Rear-wheel drive",
    character: "Open-top supercar",
    folder: "mclaren750s",
    files: [
      "750s-front.jpg",
      "750s-side.jpg",
      "750s-rear.jpg",
      "750s-interior.jpg",
      "750s-wheel.jpg"
    ]
  },
  {
    name: "Mercedes-AMG GT 63",
    category: "GRAND TOURING",
    description: "Distinctive AMG design, V8 power and grand touring refinement.",
    engine: "4.0L twin-turbo V8",
    drive: "All-wheel drive",
    character: "Performance grand tourer",
    folder: "amggt",
    files: [
      "amggt-front.jpg",
      "amggt-side.jpg",
      "amggt-rear.jpg",
      "amggt-interior.jpg",
      "amggt-wheel.jpg"
    ]
  }
];

function photoPath(carIndex, angleIndex) {
  const car = cars[carIndex];

  return `assets/images/${car.folder}/${car.files[angleIndex]}`;
}

function photoAlt(carIndex, angleIndex) {
  return `${cars[carIndex].name} — ${angles[angleIndex]} view`;
}

function pad(number) {
  return String(number).padStart(2, "0");
}

/* ============================================
   YEAR AND MOBILE NAVIGATION
============================================ */

$("#year").textContent = new Date().getFullYear();

const menuToggle = $("#menuToggle");
const navLinks = $("#navLinks");

function closeMenu() {
  navLinks.classList.remove("open");
  menuToggle.setAttribute("aria-expanded", "false");
  menuToggle.setAttribute("aria-label", "Open navigation");
}

menuToggle.addEventListener("click", () => {
  const open = navLinks.classList.toggle("open");

  menuToggle.setAttribute("aria-expanded", String(open));
  menuToggle.setAttribute(
    "aria-label",
    open ? "Close navigation" : "Open navigation"
  );
});

$$(".nav-links a").forEach((link) => {
  link.addEventListener("click", closeMenu);
});

document.addEventListener("click", (event) => {
  if (
    !navLinks.contains(event.target) &&
    !menuToggle.contains(event.target)
  ) {
    closeMenu();
  }
});

window.addEventListener("resize", () => {
  if (window.innerWidth > 800) closeMenu();
});

/* ============================================
   SCROLL PROGRESS
============================================ */

const header = $("#header");
const progress = $("#scrollProgress");
const backToTop = $("#backToTop");

function updateScroll() {
  const maxScroll =
    document.documentElement.scrollHeight -
    document.documentElement.clientHeight;

  const percentage = maxScroll > 0
    ? (window.scrollY / maxScroll) * 100
    : 0;

  progress.style.width =
    `${Math.min(100, Math.max(0, percentage))}%`;

  header.classList.toggle("scrolled", window.scrollY > 30);

  backToTop.classList.toggle(
    "visible",
    window.scrollY > 450
  );
}

window.addEventListener(
  "scroll",
  updateScroll,
  { passive: true }
);

window.addEventListener("resize", updateScroll);

backToTop.addEventListener("click", () => {
  window.scrollTo({
    top: 0,
    behavior: reducedMotion ? "auto" : "smooth"
  });
});

updateScroll();

/* ============================================
   IMAGE LOADING AND ERROR HANDLING
============================================ */

function setImage(image, source, alt) {
  const frame = image.closest(".media-frame");

  if (frame) {
    frame.classList.remove("image-error");
  }

  image.alt = alt;
  image.src = source;
}

$$(".media-frame img").forEach((image) => {
  image.addEventListener("error", () => {
    const frame = image.closest(".media-frame");

    if (frame) {
      frame.classList.add("image-error");
    }

    console.warn("Image could not be loaded:", image.src);
  });

  image.addEventListener("load", () => {
    const frame = image.closest(".media-frame");

    if (frame) {
      frame.classList.remove("image-error");
    }
  });

  if (image.complete && image.naturalWidth === 0) {
    image.closest(".media-frame")?.classList.add("image-error");
  }
});

/* ============================================
   HERO CAR SLIDER
============================================ */

let heroIndex = 0;

const heroImage = $("#heroImage");
const heroName = $("#heroName");
const heroCategory = $("#heroCategory");
const heroDescription = $("#heroDescription");
const heroCount = $("#heroCount");
const heroDots = $("#heroDots");

function renderHero() {
  const car = cars[heroIndex];

  setImage(
    heroImage,
    photoPath(heroIndex, 0),
    photoAlt(heroIndex, 0)
  );

  heroName.textContent = car.name;
  heroCategory.textContent = car.category;
  heroDescription.textContent = car.description;

  heroCount.textContent =
    `${pad(heroIndex + 1)} / ${pad(cars.length)}`;

  $("#heroStatus").textContent =
    `Now showing ${car.name}`;

  [...heroDots.children].forEach((button, index) => {
    const active = index === heroIndex;

    button.classList.toggle("active", active);
    button.setAttribute("aria-pressed", String(active));
  });
}

function changeHero(direction) {
  heroIndex =
    (heroIndex + direction + cars.length) % cars.length;

  renderHero();
}

cars.forEach((car, index) => {
  const button = document.createElement("button");

  button.type = "button";
  button.className = "hero-dot";
  button.setAttribute("aria-label", `Show ${car.name}`);

  button.addEventListener("click", () => {
    heroIndex = index;
    renderHero();
  });

  heroDots.appendChild(button);
});

$("#heroPrev").addEventListener("click", () => changeHero(-1));
$("#heroNext").addEventListener("click", () => changeHero(1));

$("#heroExplore").addEventListener("click", () => {
  selectCar(heroIndex);
});

renderHero();

/* ============================================
   COLLECTION FILTERING
============================================ */

const filterButtons = $$(".filter");
const carCards = $$(".car-card");

function applyFilter(filter) {
  let visible = 0;

  carCards.forEach((card) => {
    const match =
      filter === "all" ||
      card.dataset.category === filter;

    card.hidden = !match;

    if (match) {
      visible++;
      card.classList.add("visible");
    }
  });

  $("#collectionCount").textContent =
    `${pad(visible)} ${visible === 1 ? "MODEL" : "MODELS"}`;

  filterButtons.forEach((button) => {
    const active = button.dataset.filter === filter;

    button.classList.toggle("active", active);
    button.setAttribute("aria-pressed", String(active));
  });
}

filterButtons.forEach((button) => {
  button.addEventListener("click", () => {
    applyFilter(button.dataset.filter);
  });
});

/* ============================================
   CAR EXPLORER — ALL FIVE ANGLES
============================================ */

let selectedCarIndex = 0;
let selectedAngleIndex = 0;

const explorerImage = $("#explorerImage");
const thumbnails = $("#thumbnails");
const angleButtons = $("#angleButtons");

function createAngleControls(
  container,
  carIndex,
  activeAngle,
  onSelect,
  thumbnailsMode
) {
  container.replaceChildren();

  angles.forEach((angle, index) => {
    const button = document.createElement("button");

    button.type = "button";

    button.className = thumbnailsMode
      ? "thumb"
      : "angle-button";

    const active = index === activeAngle;

    button.classList.toggle("active", active);

    button.setAttribute(
      "aria-label",
      `Show ${angle} view`
    );

    button.setAttribute("aria-pressed", String(active));

    if (thumbnailsMode) {
      const image = document.createElement("img");
      image.src = photoPath(carIndex, index);
      image.alt = "";
      image.loading = "lazy";

      const label = document.createElement("span");
      label.textContent = angle.toUpperCase();

      button.append(image, label);
    } else {
      button.textContent =
        angle.charAt(0).toUpperCase() + angle.slice(1);
    }

    button.addEventListener("click", () => {
      onSelect(index);
    });

    container.appendChild(button);
  });
}

function renderExplorer() {
  const car = cars[selectedCarIndex];

  $("#selectedCarName").textContent = car.name;
  $("#selectedCarDescription").textContent = car.description;

  $("#specEngine").textContent = car.engine;
  $("#specDrive").textContent = car.drive;
  $("#specCharacter").textContent = car.character;

  $("#carSelect").value = String(selectedCarIndex);

  $("#explorerAngleLabel").textContent =
    `${angles[selectedAngleIndex].toUpperCase()} VIEW / ${pad(selectedAngleIndex + 1)}`;

  setImage(
    explorerImage,
    photoPath(selectedCarIndex, selectedAngleIndex),
    photoAlt(selectedCarIndex, selectedAngleIndex)
  );

  createAngleControls(
    thumbnails,
    selectedCarIndex,
    selectedAngleIndex,
    selectAngle,
    true
  );

  createAngleControls(
    angleButtons,
    selectedCarIndex,
    selectedAngleIndex,
    selectAngle,
    false
  );
}

function selectCar(index) {
  if (!cars[index]) return;

  selectedCarIndex = index;
  selectedAngleIndex = 0;

  renderExplorer();
}

function selectAngle(index) {
  if (index < 0 || index >= angles.length) return;

  selectedAngleIndex = index;
  renderExplorer();
}

$("#carSelect").addEventListener("change", (event) => {
  selectCar(Number(event.target.value));
});

$$("[data-select-car]").forEach((button) => {
  button.addEventListener("click", () => {
    selectCar(Number(button.dataset.selectCar));

    $("#explore").scrollIntoView({
      behavior: reducedMotion ? "auto" : "smooth"
    });
  });
});

/* ============================================
   FULLSCREEN PHOTO GALLERY
============================================ */

const photoDialog = $("#photoDialog");
const modalImage = $("#modalImage");

let modalAngleIndex = 0;

function renderModal() {
  const car = cars[selectedCarIndex];

  setImage(
    modalImage,
    photoPath(selectedCarIndex, modalAngleIndex),
    photoAlt(selectedCarIndex, modalAngleIndex)
  );

  $("#modalTitle").textContent = car.name;

  $("#modalCaption").textContent =
    `${angles[modalAngleIndex].toUpperCase()} VIEW`;

  $("#modalCounter").textContent =
    `${pad(modalAngleIndex + 1)} / ${pad(angles.length)}`;

  createAngleControls(
    $("#modalThumbnails"),
    selectedCarIndex,
    modalAngleIndex,
    (index) => {
      modalAngleIndex = index;
      renderModal();
    },
    true
  );
}

function moveModal(direction) {
  modalAngleIndex =
    (modalAngleIndex + direction + angles.length) %
    angles.length;

  renderModal();
}

$("#openFullscreen").addEventListener("click", () => {
  modalAngleIndex = selectedAngleIndex;
  renderModal();

  if (!photoDialog.open) {
    photoDialog.showModal();
  }

  $("#modalClose").focus();
});

$("#modalClose").addEventListener("click", () => {
  photoDialog.close();
});

$("#modalPrev").addEventListener("click", () => {
  moveModal(-1);
});

$("#modalNext").addEventListener("click", () => {
  moveModal(1);
});

photoDialog.addEventListener("click", (event) => {
  if (event.target === photoDialog) {
    photoDialog.close();
  }
});

photoDialog.addEventListener("keydown", (event) => {
  if (event.key === "ArrowLeft") {
    event.preventDefault();
    moveModal(-1);
  }

  if (event.key === "ArrowRight") {
    event.preventDefault();
    moveModal(1);
  }
});

/* ============================================
   SCROLL REVEAL ANIMATIONS
============================================ */

const reveals = $$(".reveal");

if (
  !reducedMotion &&
  "IntersectionObserver" in window
) {
  const observer = new IntersectionObserver(
    (entries, currentObserver) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          currentObserver.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.08,
      rootMargin: "0px 0px -25px 0px"
    }
  );

  reveals.forEach((element) => observer.observe(element));
} else {
  reveals.forEach((element) => {
    element.classList.add("visible");
  });
}

/* ============================================
   INITIALIZE APPLICATION
============================================ */

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") closeMenu();
});

applyFilter("all");
selectCar(0);

console.log("VELORA MOTORS V3 — Gallery initialized");
