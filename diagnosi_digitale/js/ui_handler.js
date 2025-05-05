document.getElementById("toggle_all_prompts").addEventListener("click", function() {
    const prompts = document.querySelectorAll("textarea[name^='prompt_']");
    const isHidden = Array.from(prompts).every(prompt => prompt.style.display === "none" || prompt.style.display === "");
    prompts.forEach(prompt => {
        prompt.style.display = isHidden ? "block" : "none";
    });
    this.textContent = isHidden ? "Hide All Prompts" : "Edit All Prompts";
});