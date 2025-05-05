let previousContext = ''; // Variabile per memorizzare il contesto precedente

// Log per la funzione generateAI
function generateAI(prompt) {
    console.log('Generate AI: Sending prompt to server:', prompt);

    const url = `${window.location.origin}/custom/diagnosi_digitale/script/ai_handler.php`;

    const requestData = { prompt: prompt };
    console.log('Generate AI: Request data:', requestData);

    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Generate AI: Server response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Generate AI: Received response:', data);
        if (data.success) {
            return data.content; // Return the generated content
        } else {
            console.error('Generate AI: Error from server:', data.error);
            alert('Error generating AI content: ' + data.error);
            return null;
        }
    })
    .catch(error => {
        console.error('Generate AI: Fetch error:', error);
        alert('Error generating AI content. Please try again.');
        return null;
    });
}

// Log per il pulsante "Generate"
document.querySelectorAll(".generate-button").forEach(button => {
    button.addEventListener("click", function() {
        const targetField = this.getAttribute("data-target");
        console.log(`Generate button clicked for target field: ${targetField}`);

        const promptTextarea = document.querySelector(`textarea[name="prompt_${targetField}"]`);
        const targetTextarea = document.querySelector(`textarea[name="${targetField}"]`);

        if (promptTextarea && targetTextarea) {
            const prompt = promptTextarea.value;
            console.log(`Prompt for target field ${targetField}:`, prompt);

            this.disabled = true; // Disabilita il pulsante durante la richiesta
            generateAI(prompt).then(content => {
                if (content) {
                    console.log(`AI response for target field ${targetField}:`, content);
                    targetTextarea.value = content; // Riempie la textarea con la risposta dell'AI
                }
            }).finally(() => {
                this.disabled = false; // Riabilita il pulsante
            });
        } else {
            console.error(`Prompt or target textarea not found for target field: ${targetField}`);
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const generateAIButton = document.getElementById("generate_ai");
    if (generateAIButton) {
        generateAIButton.addEventListener("click", function () {
            const contextField = document.querySelector("textarea[name='context_ai']");
            const promptField = document.querySelector("textarea[name='oggetto_valutazione']");

            if (!contextField || !promptField) {
                alert("Required fields are missing.");
                return;
            }

            const context = contextField.value.trim();
            const prompt = promptField.value.trim();

            if (!prompt) {
                alert("The prompt cannot be empty.");
                return;
            }

            const fullPrompt = context ? `${context}\n\n${prompt}` : prompt;

            generateAI(fullPrompt).then(content => {
                if (content) {
                    promptField.value = content; // Fill the prompt field with the AI response
                }
            });
        });
    }

    // Log per il pulsante "Crawl Web"
    const crawlWebButton = document.getElementById("crawl_web");
    if (crawlWebButton) {
        crawlWebButton.addEventListener("click", function () {
            console.log("Crawl Web button clicked");
            const url = document.getElementById("url_input").value;
            fetch(DOL_URL_ROOT + "/script/crawl_web.php?url=" + encodeURIComponent(url), {
                method: "GET",
                headers: {
                    "Content-Type": "application/json"
                }
            })
                .then(response => {
                    console.log("Crawl Web: Server response status:", response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("Crawl Web: Received response:", data);
                    if (data.success) {
                        const contextWebField = document.getElementById("context_web");
                        contextWebField.value = data.content;
                        alert("Website content has been loaded into the context_web field.");
                    } else {
                        alert("Error: " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Error crawling website:", error);
                    alert("An error occurred while crawling the website.");
                });
        });
    } else {
        console.error("Button with id 'crawl_web' not found in the DOM.");
    }

    const concatContextButton = document.getElementById("concat_context");
    if (concatContextButton) {
        concatContextButton.addEventListener("click", async function () {
            console.log("Concat Context button clicked");

            const contextWebField = document.getElementById("context_web");
            const contextAIField = document.getElementById("context_ai");

            if (!contextWebField || !contextAIField) {
                console.error("Context fields not found in the DOM.");
                alert("Context fields are missing.");
                return;
            }

            const contextWeb = contextWebField.value.trim();
            const contextAI = contextAIField.value.trim();

            if (!contextWeb && !contextAI) {
                alert("Both context fields are empty. Please provide at least one context.");
                return;
            }

            const promptFields = document.querySelectorAll("textarea[name^='prompt_']");
            if (promptFields.length === 0) {
                alert("No prompt fields found to concatenate.");
                return;
            }

            // Leggi il contenuto del file voucherText.txt
            let voucherText = '';
            try {
                const response = await fetch("js/voucherText.txt");
                if (!response.ok) {
                    throw new Error(`Failed to fetch voucherText.txt: ${response.statusText}`);
                }
                voucherText = await response.text();
            } catch (error) {
                console.error("Error fetching voucherText.txt:", error);
                alert("Failed to load voucher text. Please check the file.");
                return;
            }

            promptFields.forEach(promptField => {
                //const fixedPrompt = "Sei un Innovation Manager, mantieni un tono professionale e distaccato, devi compilare il un questionario relativo a un Voucher della Regione Calabria, non scrivere il nome dell'azienda, limitati a locuzioni. Le risposte devono restare tassativamente entro le 50 parole. Non usare mai il termine 'AI' o 'Artificial Intelligence'.";
                const currentPrompt = promptField.value.trim();
                currentPrompt = currentPrompt.substring(0, 2000); // Limit to 2000 characters
                const shortenedContextAI = contextAI.substring(0, 2000);

                const concatenatedPrompt = `${fixedPrompt}\n**********\n${voucherText}`.trim();
                //promptField.value = concatenatedPrompt;

                console.log(`Updated prompt for field ${promptField.name}:`, concatenatedPrompt);
            });

            alert("Contexts have been concatenated to all prompts.");
        });
    } else {
        console.error("Button with id 'concat_context' not found in the DOM.");
    }

    // --- Calculate Total ---
    // List of input field IDs that affect the total calculation
    const totalCalculationFields = [
        "digital_workplace_numero",
        "digital_workplace", // Added for completeness
        "digital_comm_engage", // Corrected ID based on calculateTotal function
        "cloud_comp_app_server",
        "cloud_comp_db_server",
        "cloud_comp_web_server",
        "cloud_comp_db_bkup_server", // Corrected ID based on calculateTotal function
        "cyber_security"
    ];

    // Add event listener to each field
    totalCalculationFields.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element) {
            element.addEventListener("input", calculateTotal);
            console.log(`Added input listener to ${fieldId}`);
        } else {
            console.error(`Element not found for total calculation: ${fieldId}`);
        }
    });

    // Initial calculation on page load
    calculateTotal();
    // --- End Calculate Total ---

    const toggleContext = document.getElementById("toggle_context");
    const contextFields = document.getElementById("context_fields");

    if (toggleContext && contextFields) {
        toggleContext.addEventListener("click", function () {
            contextFields.classList.toggle("hidden");
        });
    } else {
        console.error("Element not found: toggle_context or context_fields");
    }
});

document.querySelector('form').addEventListener('submit', function (e) {
    // Select all input and textarea elements whose name starts with "prompt_"
    const promptFields = this.querySelectorAll('input[name^="prompt_"], textarea[name^="prompt_"]');

    // Temporarily disable these fields so they are not included in the POST data
    promptFields.forEach(field => {
        field.disabled = true;
    });

    // Log the data that *will* be sent (for debugging, FormData won't show disabled fields)
    const formData = new FormData(this);
    const dataToSend = {};
    formData.forEach((value, key) => {
        dataToSend[key] = value;
    });
    console.log('Dati che verranno inviati al backend (senza campi prompt_):', dataToSend);

    // Allow the form to submit normally (without the disabled prompt_ fields)
    // No need for e.preventDefault() or this.submit() if we let the default action proceed
    // If you were using AJAX, you would construct the FormData *after* disabling and then send it.

    // Optional: Re-enable fields immediately after logging,
    // in case submission is cancelled or fails client-side before navigating away.
    // For standard form submission, this might not be strictly necessary as the page unloads.
    // promptFields.forEach(field => {
    //     field.disabled = false;
    // });
});

document.getElementById("generate_ai").addEventListener("click", function () {
    const fixedPrompt = "You are a helpful assistant. Always provide funny answers in english, even if the question is in Italian. Do not use any other language.";
    const dynamicPromptField = document.querySelector("textarea[name='dynamic_prompt']");
    const contextField = document.querySelector("textarea[name='context_ai']");

    if (!dynamicPromptField) {
        alert("Dynamic prompt field is missing.");
        return;
    }

    const dynamicPrompt = dynamicPromptField.value.trim();
    const context = contextField ? contextField.value.trim() : "";

    if (!dynamicPrompt) {
        alert("The dynamic prompt cannot be empty.");
        return;
    }

    const requestData = {
        fixed_prompt: fixedPrompt,
        dynamic_prompt: dynamicPrompt,
        context: context
    };

    fetch("/custom/diagnosi_digitale/script/ai_handler.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(requestData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                dynamicPromptField.value = data.content; // Riempie il campo con la risposta dell'AI
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred while generating AI content.");
        });
});

function calculateTotal() {
    // Ensure IDs match the ones used in the HTML/PHP form
    const digitalWorkplaceNumero = parseFloat(document.getElementById("digital_workplace_numero").value) || 0;
    const digitalCommEngage = parseFloat(document.getElementById("digital_comm_engage").value) || 0; // Check this ID in your HTML
    const cloudCompAppServer = parseFloat(document.getElementById("cloud_comp_app_server").value) || 0; // Check this ID
    const cloudCompDbServer = parseFloat(document.getElementById("cloud_comp_db_server").value) || 0; // Check this ID
    const cloudCompWebServer = parseFloat(document.getElementById("cloud_comp_web_server").value) || 0; // Check this ID
    const cloudCompDbBackUp = parseFloat(document.getElementById("cloud_comp_db_back_up").value) || 0; // Check this ID (might be cloud_comp_db_bkup_server?)

    // Adjust the calculation formula as needed
    const total = (digitalWorkplaceNumero * 2270) + digitalCommEngage + cloudCompAppServer + cloudCompDbServer + cloudCompWebServer + cloudCompDbBackUp;

    const totalField = document.getElementById("totale");
    if (totalField) {
        totalField.value = total.toFixed(2);
        console.log(`Calculated Total: ${total.toFixed(2)}`);
    } else {
        console.error("Element with ID 'totale' not found.");
    }
}
