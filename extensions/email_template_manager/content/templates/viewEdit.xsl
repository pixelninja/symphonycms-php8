<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes"/>

<xsl:template match="/">
	<form method="post" action="{$current-url}">
		<fieldset class="settings">
			<legend>Template Settings</legend>
			<div>
				<xsl:if test="/data/errors/name">
					<xsl:attribute name="class">
						<xsl:text>invalid</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<label>
					Name
					<input type="text" name="fields[name]">
						<xsl:attribute name="value">
							<xsl:choose>
								<xsl:when test="/data/fields">
									<xsl:value-of select="/data/fields/name"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="/data/templates/entry/name"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</input>
				</label>
				<xsl:if test="/data/errors/name">
					<p><xsl:value-of select="/data/errors/name"/></p>
				</xsl:if>
			</div>
			<label>
				Datasources
				<i>optional</i>
				<select multiple="multiple" name="fields[datasources][]">
					<xsl:for-each select="/data/datasources/entry">
						<option value="{handle}">
							<xsl:choose>
								<xsl:when test="/data/fields">
									<xsl:if test="/data/fields/datasources/item = handle">
										<xsl:attribute name="selected" select="'selected'"/>
									</xsl:if>
								</xsl:when>
								<xsl:otherwise>
									<xsl:if test="/data/templates/entry/datasources/item = handle">
										<xsl:attribute name="selected" select="'selected'"/>
									</xsl:if>
								</xsl:otherwise>
							</xsl:choose>
							<xsl:value-of select="name"/>
						</option>
					</xsl:for-each>
					<xsl:if test="not(/data/datasources/entry)">
						<option value="0" disabled="1">No datasources installed</option>
					</xsl:if>
				</select>
			</label>
			<p class="help">Layouts will be able to use these datasources to build their content.</p>
			<label>
				Layouts
				<select name="fields[layouts]">
					<option value="both">
						<xsl:if test="(/data/templates/entry/layouts/html) and (/data/templates/entry/layouts/plain) or (not(/data/templates/entry/layouts/html) and not(/data/templates/entry/layouts/plain))">
							<xsl:attribute name="selected">1</xsl:attribute>
						</xsl:if>
						HTML and Plain
					</option>
					<option value="html">
						<xsl:if test="not(/data/templates/entry/layouts/plain) and (/data/templates/entry/layouts/html)">
							<xsl:attribute name="selected">1</xsl:attribute>
						</xsl:if>
						HTML only
					</option>
					<option value="plain">
						<xsl:if test="not(/data/templates/entry/layouts/html) and (/data/templates/entry/layouts/plain)">
							<xsl:attribute name="selected">1</xsl:attribute>
						</xsl:if>
						Plain only
					</option>
				</select>
			</label>
			<p class="help">Only the layouts selected will be emailed.</p>
		</fieldset>
		<fieldset class="settings">
			<legend>Email Settings</legend>
			<p class="help">These settings are global settings for this template. They can be overwritten by extensions or custom events. <br /><br />Use the {$param} and {/xpath/query} notation to include dynamic parts. It is not possible to combine the two syntaxes: {/xpath/$param} is not possible.</p>
			<div>
				<xsl:if test="/data/errors/subject">
					<xsl:attribute name="class">
						<xsl:text>invalid</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<label>
					Subject
					<input type="text" name="fields[subject]">
						<xsl:attribute name="value">
							<xsl:choose>
								<xsl:when test="/data/fields">
									<xsl:value-of select="/data/fields/subject"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="/data/templates/entry/subject"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</input>
				</label>
				<xsl:if test="/data/errors/subject">
					<p><xsl:value-of select="/data/errors/subject"/></p>
				</xsl:if>
				<xsl:if test="not(/data/errors/subject)">
					<p class="help">Use the {$param} and {/xpath/query} notation to include dynamic parts. It is not possible to combine the two syntaxes. If the XPath returns more than one result, only the first is used</p>
				</xsl:if>
			</div>
			<div>
				<xsl:if test="/data/errors/recipients">
					<xsl:attribute name="class">
						<xsl:text>invalid</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<label>
					Recipients
					<i>optional</i>
					<input type="text" name="fields[recipients]">
						<xsl:attribute name="value">
							<xsl:choose>
								<xsl:when test="/data/fields">
									<xsl:value-of select="/data/fields/recipients"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="/data/templates/entry/recipients"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</input>
				</label>
				<xsl:if test="/data/errors/recipients">
					<p><xsl:value-of select="/data/errors/recipients"/></p>
				</xsl:if>
				<xsl:if test="not(/data/errors/recipients)">
					<p class="help">Select multiple recipients by separating them with commas. This is also possible dynamically: <code>{/data/authors/author/name} &lt;{/data/authors/author/email}&gt;</code> will return: <code>name &lt;email@domain.com&gt;, name2 &lt;email2@domain.com&gt;</code></p>
				</xsl:if>
			</div>
			<div class="group">
				<div>
					<xsl:if test="/data/errors/from-name">
						<xsl:attribute name="class">
							<xsl:text>invalid</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<label>
						From Name
						<i>optional</i>
						<input type="text" name="fields[from-name]">
							<xsl:attribute name="value">
								<xsl:choose>
									<xsl:when test="/data/fields">
										<xsl:value-of select="/data/fields/from-name"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="/data/templates/entry/from-name"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
						</input>
					</label>
					<xsl:if test="/data/errors/from-name">
						<p><xsl:value-of select="/data/errors/from-name"/></p>
					</xsl:if>
				</div>
				<div>
					<xsl:if test="/data/errors/from-email-address">
						<xsl:attribute name="class">
							<xsl:text>invalid</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<label>
						From Email Address
						<i>optional</i>
						<input type="text" name="fields[from-email-address]">
							<xsl:attribute name="value">
								<xsl:choose>
									<xsl:when test="/data/fields">
										<xsl:value-of select="/data/fields/from-email-address"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="/data/templates/entry/from-email-address"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
						</input>
					</label>
					<xsl:if test="/data/errors/from-email-address">
						<p><xsl:value-of select="/data/errors/from-email-address"/></p>
					</xsl:if>
					<xsl:if test="not(/data/errors/from-email-address)">
						<p class="help">Faking this address is dangerous. It may cause issues with spam filters and even break sending, especially via SMTP.</p>
					</xsl:if>
				</div>
			</div>
			<div class="group">
				<div>
					<xsl:if test="/data/errors/reply-to-name">
						<xsl:attribute name="class">
							<xsl:text>invalid</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<label>
						Reply-To Name
						<i>optional</i>
						<input type="text" name="fields[reply-to-name]">
							<xsl:attribute name="value">
								<xsl:choose>
									<xsl:when test="/data/fields">
										<xsl:value-of select="/data/fields/reply-to-name"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="/data/templates/entry/reply-to-name"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
						</input>
					</label>
					<xsl:if test="/data/errors/reply-to-name">
						<p><xsl:value-of select="/data/errors/reply-to-name"/></p>
					</xsl:if>
				</div>
				<div>
					<xsl:if test="/data/errors/reply-to-email-address">
						<xsl:attribute name="class">
							<xsl:text>invalid</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<label>
						Reply-To Email Address
						<i>optional</i>
						<input type="text" name="fields[reply-to-email-address]">
							<xsl:attribute name="value">
								<xsl:choose>
									<xsl:when test="/data/fields">
										<xsl:value-of select="/data/fields/reply-to-email-address"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="/data/templates/entry/reply-to-email-address"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
						</input>
					</label>
					<xsl:if test="/data/errors/reply-to-email-address">
						<p><xsl:value-of select="/data/errors/reply-to-email-address"/></p>
					</xsl:if>
				</div>
			</div>
			<div>
				<xsl:if test="/data/errors/attachments">
					<xsl:attribute name="class">
						<xsl:text>invalid</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<label>
					Attachments
					<i>optional</i>
					<input type="text" name="fields[attachments]">
						<xsl:attribute name="value">
							<xsl:choose>
								<xsl:when test="/data/fields">
									<xsl:value-of select="/data/fields/attachments"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="/data/templates/entry/attachments"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</input>
				</label>
				<xsl:if test="/data/errors/attachments">
					<p><xsl:value-of select="/data/errors/attachments"/></p>
				</xsl:if>
				<xsl:if test="not(/data/errors/attachments)">
					<p class="help">Select multiple attachments by separating them with commas. For each file define either a URL or a local path starting from the DOCROOT, e.g. <code>/workspace/media/foo.pdf</code>. It is also possible to include dynamic parts. URLs and local paths must not contain commas.</p>
				</xsl:if>
			</div>
			<div>
				<xsl:if test="/data/errors/ignore-attachment-errors">
					<xsl:attribute name="class">
						<xsl:text>invalid</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<label>
					<input type="checkbox" name="fields[ignore-attachment-errors]">
						<xsl:choose>
							<xsl:when test="/data/fields/ignore-attachment-errors">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:when>
							<xsl:when test="/data/templates/entry/ignore-attachment-errors">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:when>
						</xsl:choose>
					</input>
					<xsl:text> </xsl:text>
					<xsl:text>Ignore attachment errors</xsl:text>
				</label>
				<xsl:if test="/data/errors/attachments">
					<p><xsl:value-of select="/data/errors/attachments"/></p>
				</xsl:if>
				<xsl:if test="not(/data/errors/attachments)">
					<p class="help">With the above option an email will be sent even if an attachment can not be loaded.</p>
				</xsl:if>
			</div>
		</fieldset>
		<div class="actions">
			<xsl:if test="/data/xsrf_input">
				<xsl:copy-of select="/data/xsrf_input/*"/>
			</xsl:if>
			<input type="submit" accesskey="s" name="action[save]">
				<xsl:attribute name="value">
					<xsl:choose>
						<xsl:when test="/data/templates/entry/name">Save Changes</xsl:when>
						<xsl:otherwise>Create Template</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
			</input>
			<xsl:if test="not(/data/context/item[@index=1] = 'new')" >
				<button accesskey="d" title="Delete this page" class="button confirm delete" name="action[delete]">Delete</button>
			</xsl:if>
		</div>
		</form>
</xsl:template>

</xsl:stylesheet>
