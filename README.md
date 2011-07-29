Phing Extensions
================

These are some useful Phing extensions that I wrote.

YuiCompressorFilter
-------------------

A filter to run your JavaScript and CSS assets through the yui-compressor.
You need to have yui-compressor installed on your system.

The following example task concatenates all JavaScript files into one file in
the build directory and compresses them in the process.

'''xml
<target name="js-compress">
    <delete file="${project.basedir}/build/js/main.js" />
    <append destFile="${project.basedir}/build/js/main.js">
        <filterchain>
            <filterreader classname="path.to.filters.YuiCompressorFilter">
                <param name="type" value="js" />
                <param name="preserve-semi" value="true" />
            </filterreader>
        </filterchain>
        <filelist dir="src/js" files="forms.js,validation.js,gallery.js" />
    </append>
</target>
'''

The YuiCompressorFilter supports the following parameters:

 * bin: Path to the yui-compressor.
 * encoding: Set the encoding of the input stream (defaults to utf-8).
 * type: Specifies the type of the input file (js or css).
 * line-break: Insert a line break after the specified column number.
 * verbose: Display informational messages and warnings.
 * nomunge: Minify only, do not obfuscate.
 * preserve-semi: Preserve all semicolons.
 * disable-optimizations: Disable all micro optimizations.
