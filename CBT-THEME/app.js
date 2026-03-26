window.onload = () => {
  const transitionEl = document.querySelector(".transition");
  const anchor = document.querySelectorAll("a");

  setTimeout(() => {
    transitionEl.classList.remove("is-active");
  }, 800);

  for (let i = 0; i < anchor.length; i++) {
    const anc = anchor[i];

    anc.addEventListener("click", (e) => {
      e.preventDefault();

      let target = e.target.href;
      transitionEl.classList.add("is-active");

      setTimeout(() => {
        window.location.href = target;
      }, 800);
    });
  }
};
