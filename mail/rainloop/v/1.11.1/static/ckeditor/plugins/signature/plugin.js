
(function(CKEDITOR, $, undefined) {

	'use strict';

	var rl_signature_replacer = function(editor, text, signature, isHtml, insertBefore, isFirst) {

		var
			skipInsert = false,
			isEmptyText = false,
			newLine = (isHtml ? '<br />' : "\n"),
			clearHtmlLine = function(html) {
				return $.trim(editor.__plainUtils.htmlToPlain(html));
			};

		isEmptyText = '' === $.trim(text);
		if (!isEmptyText && isHtml)
		{
			isEmptyText = '' === clearHtmlLine(text);
		}

		if (editor.__previos_signature && !isEmptyText)
		{
			if (isHtml && !editor.__previos_signature_is_html)
			{
				editor.__previos_signature = editor.__plainUtils.plainToHtml(editor.__previos_signature);
				editor.__previos_signature_is_html = true;
			}
			else if (!isHtml && editor.__previos_signature_is_html)
			{
				editor.__previos_signature = editor.__plainUtils.htmlToPlain(editor.__previos_signature);
				editor.__previos_signature_is_html = false;
			}

			skipInsert = false;
			if (isHtml)
			{
				var clearSig = clearHtmlLine(editor.__previos_signature);
				text = text.replace(/<signature>([\s\S]*)<\/signature>/igm, function(all){
					var c = clearSig === clearHtmlLine(all);
					if (!c) {
						skipInsert = true;
					}
					return c ? '' : all;
				});
			}
			else
			{
				var textLen = text.length;
				text = text
					.replace('' + editor.__previos_signature, '')
					.replace('' + editor.__previos_signature, '');

				if (textLen === text.length)
				{
					skipInsert = true;
				}
			}
		}

		if (!skipInsert)
		{
			if (isEmptyText)
			{
				signature = newLine + newLine + signature;
			}
			else
			{
				signature = insertBefore ? signature + newLine + newLine : newLine + newLine + signature;
			}

			if (isHtml)
			{
				signature = '<signature>' + signature + '</signature>';
			}

			text = insertBefore ? signature + text : text + signature;

			if (10 < signature.length)
			{
				editor.__previos_signature = signature;
				editor.__previos_signature_is_html = isHtml;
			}
		}

		return text;
	};

	CKEDITOR.plugins.add('signature', {
		init: function(editor) {
			editor.addCommand('insertSignature', {
				modes: {wysiwyg: 1, plain: 1},
				exec: function(editor, cfg) {

					if (cfg && cfg.clearCache)
					{
						editor.__previos_signature = undefined;
						editor.__previos_signature_is_html = undefined;
						return true;
					}

					var
						bIsHtml = false,
						bIsFirst = false,
						bInsertBefore = false,
						sSignature = '',
						sResultSignature = '';

					if (cfg)
					{
						bIsHtml = undefined === cfg.isHtml ? false : !!cfg.isHtml;
						bIsFirst = undefined === cfg.isFirst ? false : !!cfg.isFirst;
						bInsertBefore = undefined === cfg.insertBefore ? false : !!cfg.insertBefore;
						sSignature = undefined === cfg.signature ? '' : cfg.signature;
					}

					sResultSignature = sSignature;

					try
					{
						if ('plain' === editor.mode && editor.__plain && editor.__plainUtils)
						{
							if (editor.__plainUtils && editor.__plainUtils.htmlToPlain)
							{
								if (bIsHtml)
								{
									sResultSignature = editor.__plainUtils.htmlToPlain(sResultSignature);
								}
							}

							editor.__plain.setRawData(
								rl_signature_replacer(editor, editor.__plain.getRawData(), sResultSignature, false, bInsertBefore, bIsFirst));

						}
						else
						{
							if (editor.__plainUtils && editor.__plainUtils.plainToHtml)
							{
								if (!bIsHtml)
								{
									sResultSignature = editor.__plainUtils.plainToHtml(sResultSignature);
								}
							}

							editor.setData(
								rl_signature_replacer(editor, editor.getData(), sResultSignature, true, bInsertBefore, bIsFirst));
						}
					}
					catch (e) {}
				}
			});
		}
	});

}(CKEDITOR, $));
