const contactForm = document.getElementById("contactForm");
const contactStatus = document.getElementById("contactStatus");

let contactCsrfToken = "";

function setContactStatus(message, isError = false) {
  if (!contactStatus) return;

  contactStatus.textContent = message;
  contactStatus.className = isError
    ? "rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    : "rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700";
  contactStatus.classList.remove("hidden");
}

async function loadContactCsrfToken() {
  const response = await fetch("api/contact.php", {
    headers: {
      Accept: "application/json"
    }
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.error || "Impossible de preparer le formulaire.");
  }

  contactCsrfToken = typeof data.csrf_token === "string" ? data.csrf_token : "";
}

if (contactForm) {
  loadContactCsrfToken().catch((error) => {
    setContactStatus(error.message || "Impossible de charger la protection du formulaire.", true);
  });

  contactForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(contactForm);
    const payload = Object.fromEntries(formData.entries());

    try {
      if (!contactCsrfToken) {
        await loadContactCsrfToken();
      }

      const response = await fetch("api/contact.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-Token": contactCsrfToken
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json().catch(() => ({}));

      if (typeof data.csrf_token === "string" && data.csrf_token !== "") {
        contactCsrfToken = data.csrf_token;
      }

      if (!response.ok) {
        throw new Error(data.error || "Envoi impossible.");
      }

      setContactStatus(data.message || "Message envoye avec succes.");
      contactForm.reset();
    } catch (error) {
      setContactStatus(error.message || "Une erreur est survenue.", true);
    }
  });
}
