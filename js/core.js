function checkFormula() { //pro prohlizece bez html 5

    var formula = document.getElementById('formula');
    var filter = "[0-9 \(\)\+\.\/\*\-\–\,\ˆ\^]+";
  

    if (!filter.test(formula.value)) {
    alert('Prosím zadejte příklad ve správném tvaru.');
    formula.focus;
    return false;
    }
 }