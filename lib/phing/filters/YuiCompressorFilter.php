<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
*/

include_once 'phing/filters/BaseParamFilterReader.php';
include_once 'phing/filters/ChainableReader.php';

/**
 * This filter requires yui-compressor to be installed
 * 
 * <p>
 * Example:<br/>
 * <pre>
 * <filterchain>
 *   <filterreader classname="path.to.filters.YuiCompressorFilter">
 *     <param name="type" value="js" />
 *     <param name="preserve-semi" value="true" />
 *   </filterreader>
 * </filterchain>
 * </pre>
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Sander Marechal <s.marechal@jejik.com>
 * @package phing.filters
 */
class YuiCompressorFilter extends BaseParamFilterReader implements ChainableReader {
    
    /** @var string Path to the yui-compressor. */
    private $bin = 'yui-compressor';
   
    /** @var string Encoding of input stream. */
    private $encoding = 'utf8';
   
    /** @var string Specifies the type of the input file <js|css>. */
    private $type = 'js';

    /** @var string Insert a line break after the specified column number. */
    private $line_break = false;

    /** @var string Display informational messages and warnings. */
    private $verbose = false;

    /** @var string Minify only, do not obfuscate. */
    private $nomunge = false;

    /** @var string Preserve all semicolons. */
    private $preserve_semi = false;

    /** @var string Disable all micro optimizations. */
    private $disable_optimizations = false;

    /**
    /* Path to the yui-compressor.
     * @param string $bin
     */
    public function setBin($bin) {
        $this->bin = $bin;
    }

    /**
     * Set the encoding of the input stream.
     * @param string $encoding
     */
    public function setEncoding($encoding) {
        $this->encoding = $encoding;
    }

    /**
    /* Specifies the type of the input file <js|css>.
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
    /* Insert a line break after the specified column number.
     * @param bool $line_break
     */
    public function setLineBreak($line_break) {
        $this->line_break = $line_break;
    }

    /**
    /* Display informational messages and warnings.
     * @param bool $verbose
     */
    public function setVerbose($verbose) {
        $this->verbose = $verbose;
    }

    /**
    /* Minify only, do not obfuscate.
     * @param bool $nomunge
     */
    public function setNomunge($nomunge) {
        $this->nomunge = $nomunge;
    }

    /**
    /* Preserve all semicolons.
     * @param bool $preserve_semi
     */
    public function setPreserveSemi($preserve_semi) {
        $this->preserve_semi = $preserve_semi;
    }

    /**
    /* Disable all micro optimizations.
     * @param bool $disable_optimizations
     */
    public function setDisableOptimizations($disable_optimizations) {
        $this->disable_optimizations = $disable_optimizations;
    }
    
    /**
     * Reads input and returns Yui-compressed output.
     * 
     * @return the resulting stream, or -1 if the end of the resulting stream has been reached
     * 
     * @throws IOException if the underlying stream throws an IOException
     *                        during reading     
     *
     * @throws BuildException if the yui-compressor process failed
     */
    function read($len = null) {
        
        if ( !$this->getInitialized() ) {
            $this->_initialize();
            $this->setInitialized(true);
        }
        
        $buffer = $this->in->read($len);
        if($buffer === -1) {
            return -1;
        }

        $command  = $this->bin;
        $command .= ' --charset ' . $this->encoding;
        $command .= ' --type ' . $this->type;

        $flags = array('line_break', 'verbose', 'nomunge', 'preserve_semi', 'disable_optimizations');
        foreach ($flags as $flag) {
            if ($this->$flag) {
                $command .= ' --' . str_replace('_', '-', $flag);
            }
        }
        
        $descriptorspec = array(
            array('pipe', 'r'),
            array('pipe', 'w'),
            array('pipe', 'w'),
        );

        $process = proc_open($command, $descriptorspec, $pipes);
        if (!is_resource($process)) {
            throw new BuildException(sprintf('Failed to run "%s"', $config['bin']));
        }

        fwrite($pipes[0], $buffer);
        fclose($pipes[0]);

        $result = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        if (proc_close($process) > 0) {
            throw new BuildException("YUI Compressor failed with the following error:\n" . $command . ': ' . $errors);
        }

        return $result;
    }

    /**
     * Creates a new YuiCompressorFilter using the passed in Reader for instantiation.
     * 
     * @param reader A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return a new filter based on this configuration, but filtering
     *         the specified reader
     */
    public function chain(Reader $reader) {
        $newFilter = new YuiCompressorFilter($reader);
        $newFilter->setBin($this->bin);
        $newFilter->setEncoding($this->encoding);
        $newFilter->setType($this->type);
        $newFilter->setLineBreak($this->line_break);
        $newFilter->setVerbose($this->verbose);
        $newFilter->setNomunge($this->nomunge);
        $newFilter->setPreserveSemi($this->preserve_semi);
        $newFilter->setDisableOptimizations($this->disable_optimizations);
        $newFilter->setProject($this->getProject());
        return $newFilter;
    }
    
    /**
     * Initializes any parameters
     * This method is only called when this filter is used through a <filterreader> tag in build file.
     */
    private function _initialize() {
        $params = $this->getParameters();
        if ($params) {
            foreach($params as $param) {
                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('-', ' ', $param->getName())));
                if (!method_exists($this, $setter)) {
                    throw new BuildException(
                        sprintf('Unknown parameter "%s" for YuiCompressorFilter', $param->getName())
                    );
                }

                call_user_func(array($this, $setter), $param->getValue());
            }
        }
    }
}
