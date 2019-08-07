FCKConfig.EditorAreaCSS = '/include/editor/api/css/editorarea/default.css' ;

FCKConfig.AutoDetectLanguage	= false ;
FCKConfig.DefaultLanguage		= 'zh-cn' ;

FCKConfig.ToolbarSets["default"] = [										  
['Source','DocProps','-','Save','NewPage','Preview','-','Templates'],
['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
'/',
['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
['OrderedList','UnorderedList','-','Outdent','Indent','Blockquote','CreateDiv'],
['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
['Link','Unlink','Anchor'],
['DlgPicture','DlgVideo','DlgMp3','Image','Flash','Table','Rule','Smiley','SpecialChar','PageBreak'],
'/',
['Style','FontFormat','FontName','FontSize'],
['TextColor','BGColor'],
['FitWindow','ShowBlocks','-','About']		// No comma for the last row.
] ;

FCKConfig.FontNames		= '宋体;黑体;楷体;隶书;Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana' ;

FCKConfig.LinkUpload = false ;
FCKConfig.ImageUpload = false ;
FCKConfig.FlashUpload = false ;

FCKConfig.ToolbarStartExpanded    = true ;
FCKConfig.ToolbarCanCollapse = false;

FCKConfig.EnterMode = 'br' ;