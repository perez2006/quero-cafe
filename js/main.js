(function () {
    "use strict";

    const shell = document.querySelector(".dashboard-shell");

    if (!shell) {
        return;
    }

    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const toastMessage = shell.dataset.toastMessage || "";
    const recordType = shell.dataset.recordType || "";
    const recordPerson = shell.dataset.recordPerson || "";

    function pickToastMessage() {
        const fallbackMessages = [
            "Cafe registrado. A firma continua de pe.",
            "Estoque atualizado. O caos foi adiado.",
            "Cafe aberto. Agora vai."
        ];

        if (recordType === "trouxe") {
            return recordPerson
                ? recordPerson + " salvou a manha da equipe."
                : "Fulano trouxe cafe. Ainda ha esperanca.";
        }

        return toastMessage || fallbackMessages[Math.floor(Math.random() * fallbackMessages.length)];
    }

    function showToast() {
        if (!toastMessage) {
            return;
        }

        const toast = document.createElement("div");
        toast.className = "coffee-toast";
        toast.setAttribute("role", "status");
        toast.textContent = pickToastMessage();
        document.body.appendChild(toast);

        window.setTimeout(function () {
            toast.remove();
        }, 4400);
    }

    function throwCoffeeBeans() {
        if (reducedMotion || recordType !== "trouxe") {
            return;
        }

        const layer = document.createElement("div");
        layer.className = "coffee-confetti";
        document.body.appendChild(layer);

        for (let i = 0; i < 34; i += 1) {
            const bean = document.createElement("span");
            bean.className = "coffee-bean";
            bean.style.left = Math.random() * 100 + "vw";
            bean.style.setProperty("--bean-drift", (Math.random() * 220 - 110) + "px");
            bean.style.setProperty("--bean-rotate", Math.floor(Math.random() * 220) + "deg");
            bean.style.setProperty("--bean-duration", (1.15 + Math.random() * 1.2) + "s");
            bean.style.animationDelay = (Math.random() * 0.35) + "s";
            layer.appendChild(bean);
        }

        window.setTimeout(function () {
            layer.remove();
        }, 2800);
    }

    showToast();
    throwCoffeeBeans();
})();
