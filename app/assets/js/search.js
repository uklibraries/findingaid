function gEBI(id) {
    return document.getElementById(id);
}

var searchResultApplier;

function toggleItalicYellowBg() {
    searchResultApplier.toggleSelection();
}

function initFind() {
    // Enable buttons
    var classApplierModule = rangy.modules.ClassApplier;
    if (rangy.supported && classApplierModule && classApplierModule.supported) {
        searchResultApplier = rangy.createClassApplier("searchResult");

        var searchBox = gEBI("search"),
            regexCheckBox = gEBI("regex"),
            caseSensitiveCheckBox = gEBI("caseSensitive"),
            wholeWordsOnlyCheckBox = gEBI("wholeWordsOnly"),
            timer;

        function doSearch() {
            // Remove existing highlights
            var range = rangy.createRange();
            var caseSensitive = caseSensitiveCheckBox.checked;
            var searchScopeRange = rangy.createRange();
            //searchScopeRange.selectNodeContents(document.getElementById("content"));
            searchScopeRange.selectNodeContents(document.body);

            var options = {
                caseSensitive: caseSensitive,
                wholeWordsOnly: wholeWordsOnlyCheckBox.checked,
                withinRange: searchScopeRange,
                direction: "forward" // This is redundant because "forward" is the default
            };

            range.selectNodeContents(document.body);

            searchResultApplier.undoToRange(range);

            // Create search term
            var searchTerm = searchBox.value;

            if (searchTerm !== "") {
                if (regexCheckBox.checked) {
                    searchTerm = new RegExp(searchTerm, caseSensitive ? "g" : "gi");
                }

                // Iterate over matches
                while (range.findText(searchTerm, options)) {
                    // range now encompasses the first text match
                    searchResultApplier.applyToRange(range);

                    // Collapse the range to the position immediately after the match
                    range.collapse(false);
                }
            }

            timer = null;
        }

        function scheduleSearch() {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(doSearch, 500);
        }

        searchBox.onpropertychange = function() {
            if (window.event.propertyName == "value") {
                scheduleSearch();
            }
        };

        searchBox.oninput = function() {
            if (searchBox.onpropertychange) {
                searchBox.onpropertychange = null;
            }
            scheduleSearch();
        };

        regexCheckBox.onclick = scheduleSearch;
        caseSensitiveCheckBox.onclick = scheduleSearch;
        wholeWordsOnlyCheckBox.onclick = scheduleSearch;
    }
}
