/**
* This library is intended to simplify client mechanics writing.
*
* It will encompass many little developement tricks such as text list management, ...
**/

/*
* adds a value to a comma separated list, ensuring value is unique. The list is the value content of the 
* provided text. The function returns the modified list. The list does not care about value ordering.
*/
function addToList(textlist, value)
{
   unescaped_value = value;
   // escapes all regex meta
   value = value.replace(/ /g, "\\s");
   value = value.replace(/\(/g, "\\(");
   value = value.replace(/\)/g, "\\)");
   value = value.replace(/\[/g, "\\[");
   value = value.replace(/\]/g, "\\]");
   value = value.replace(/\+/g, "\\+");
   value = value.replace(/\./g, "\\.");
   value = value.replace(/\*/g, "\\*");
   value = value.replace(/\^/g, "\\^");
   value = value.replace(/\$/g, "\\$");
   value = value.replace(/\?/g, "\\?");
   regex1 = "(^|,)" + value + "(,|$)";
   re = new RegExp(regex1);
   re.compile();
   if (!textlist.search(re))
   {
      textlist += "," + unescaped_value;
   }
   // removes first comma
   if (textlist.charAt(0) == ",")
      textlist = textlist.substring(1);
   self.status = "'" + textlist + "'";
   return textlist;
}

function delFromList(textlist, value)
{
   // escapes all regex meta
   value = value.replace(/ /g, "\\s");
   value = value.replace(/\(/g, "\\(");
   value = value.replace(/\)/g, "\\)");
   value = value.replace(/\[/g, "\\[");
   value = value.replace(/\]/g, "\\]");
   value = value.replace(/\+/g, "\\+");
   value = value.replace(/\./g, "\\.");
   value = value.replace(/\* /g, "\\*");
   value = value.replace(/\^/g, "\\^");
   value = value.replace(/\$/g, "\\$");
   value = value.replace(/\?/g, "\\?");
   reS = new RegExp("^" + value + "(,|$)");
   reM = new RegExp("," + value + ",");
   reE = new RegExp("," + value + "$");
   textlist = textlist.replace(reS, "");
   textlist = textlist.replace(reM, ",");
   textlist = textlist.replace(reE, "");
   self.status = "'" + textlist + "'";
   return textlist;
}

function isInList(textlist, value)
{
   regex1 = "(^|,)" + value + "(,|$)";
   re = new RegExp(regex1);
   if (textlist.match(re))
       return true;
   return false;
}

function toggleListState(textlist, checkboxId, value){
   var obj = document.getElementById(checkboxId);
   if (obj.checked == 1){
      textlist = addToList(textlist, value);
   }
   else{
      textlist = delFromList(textlist, value);
   }
   return textlist;
}
